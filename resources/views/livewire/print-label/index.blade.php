<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Livewire\Attributes\Session;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\OrderLabel;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'printlabel_per_page')]
    public int $perPage = 10;

    #[Session(key: 'printlabel_date1')]
    public string $date1 = '';

    #[Session(key: 'printlabel_date2')]
    public string $date2 = '';

    #[Session(key: 'printlabel_code')]
    public string $code = '';

    public string $searchInput = '';

    #[Session(key: 'printlabel_status')]
    public string $status = '';

    #[Session(key: 'printlabel_print_status')]
    public string $print_status = 'not_printed';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function updated($property): void
    {
        if (in_array($property, ['code', 'status', 'print_status'])) {
            $this->updateFilterCount();
            $this->resetPage();
        }
    }

    public function mount(): void
    {
        // Gate::authorize('view print-label'); // Commented out until permissions are created

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'platform', 'label' => 'Platform', 'sortable' => false, 'class' => 'whitespace-nowrap'],
            ['key' => 'code', 'label' => 'Code', 'searchable' => true],
            ['key' => 'print_status', 'label' => 'Status', 'sortable' => false, 'disableLink' => true],
            ['key' => 'order_date', 'label' => 'Order Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'page_number', 'label' => 'Page', 'class' => 'text-center'],
            ['key' => 'file_download', 'label' => 'Download', 'disableLink' => true, 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        if (! Schema::hasTable('sales_order')) {
            $this->error('Database table `sales_order` does not exist. Please run migrations.');
            return;
        }

        $orderLabel = OrderLabel::create([
            'code' => uniqid(),
            'order_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'invoice_type' => 'SO',
            'status' => 'open',
            'saved' => 0, // Mark as draft until properly saved
        ]);

        $this->redirectRoute('print-label.edit', $orderLabel->id);
    }

    public function orderLabels(): LengthAwarePaginator
    {
        if (! Schema::hasTable('order_label')) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        return OrderLabel::stored()
            ->with('threePl')
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
            ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->when($this->print_status === 'printed', fn($q) => $q->whereNotNull('printed_at'))
            ->when($this->print_status === 'not_printed', fn($q) => $q->whereNull('printed_at'))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'orderLabels' => $this->orderLabels(),
            'headers' => $this->headers(),
        ];
    }

    public function search(): void
    {
        $data = $this->validate([
            'date1' => 'required|date',
            'date2' => 'required|date|after_or_equal:date1',
            'code' => 'nullable',
        ]);
    }

    public function searchAndClear(): void
    {
        if (!empty($this->searchInput)) {
            $this->code = $this->searchInput;
            $this->searchInput = '';
            $this->resetPage();
        }
    }

    public function searchAndPrint(): void
    {
        if (!empty($this->searchInput)) {
            $orderLabel = OrderLabel::stored()
                ->where('code', 'like', '%' . $this->searchInput . '%')
                ->first();

            if ($orderLabel) {
                // Check if order is already printed but filter is set to not_printed
                if ($this->print_status === 'not_printed' && $orderLabel->printed_at) {
                    $this->warning(
                        'Order "' . $orderLabel->code . '" sudah berstatus Printed. ' .
                        'Ubah filter Print Status untuk melihat order ini.',
                        position: 'toast-top toast-center',
                        timeout: 5000
                    );
                    // Keep print_status as 'not_printed' - user must manually change it
                    $this->searchInput = '';
                    return;
                }

                if ($orderLabel->file_path) {
                    // Update print status
                    $orderLabel->update([
                        'printed_at' => now(),
                        'printed_by' => auth()->id() ?? 0,
                        'print_count' => ($orderLabel->print_count ?? 0) + 1,
                    ]);

                    $pdfUrl = \Illuminate\Support\Facades\Storage::url($orderLabel->file_path);
                    \Log::info('SearchAndPrint: Dispatching print popup for ' . $pdfUrl);
                    $this->dispatch('open-print-popup', url: $pdfUrl);
                } else {
                    $this->warning('File not found for this order label.');
                }
            } else {
                $this->warning('No order label found with code: ' . $this->searchInput);
            }

            $this->code = $this->searchInput;
            $this->searchInput = '';
            $this->resetPage();
        }
    }

    public function markAsPrinted(int $id): void
    {
        $orderLabel = OrderLabel::find($id);

        if ($orderLabel) {
            $orderLabel->update([
                'printed_at' => now(),
                'printed_by' => auth()->id() ?? 0,
                'print_count' => ($orderLabel->print_count ?? 0) + 1,
            ]);
        }
    }

    public function clearSearch(): void
    {
        $this->code = '';
        $this->searchInput = '';
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['code','status','print_status']);
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function delete(SalesOrder $salesOrder): void
    {
        $salesOrder->delete();
        $this->success('Sales Order deleted successfully.');
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->status)) $count++;
        if (!empty($this->print_status)) $count++;
        $this->filterCount = $count;
    }

    public function clearFilter(): void
    {
        $this->code = '';
        $this->batch_no = '';
        $this->status = '';
        $this->print_status = '';
        $this->updateFilterCount();
    }



    public function export()
    {
        try {
            logger('Export started');

            $orderLabels = OrderLabel::stored()
                ->with('threePl')
                ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
                ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
                ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
                ->when($this->print_status === 'printed', fn($q) => $q->whereNotNull('printed_at'))
                ->when($this->print_status === 'not_printed', fn($q) => $q->whereNull('printed_at'))
                ->orderBy('id')
                ->get();

            logger('Found ' . $orderLabels->count() . ' records');

            if ($orderLabels->isEmpty()) {
                $this->warning('No data to export.');
                return;
            }

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Platform', 'Code', 'Print Status', 'Print Count', 'Order Date', 'Page', 'PDF Link'];
            $sheet->fromArray($headers, null, 'A1');

            // Style header row
            $headerStyle = $sheet->getStyle('A1:G1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            // Add data rows
            $row = 2;
            foreach ($orderLabels as $orderLabel) {
                // Generate public download URL (no auth required)
                $pdfUrl = '';
                if ($orderLabel->file_path) {
                    $pdfUrl = route('print-label.public-download', [
                        'path' => urlencode($orderLabel->file_path),
                        'page' => $orderLabel->page_number,
                        'label_id' => $orderLabel->id
                    ]);
                }

                $sheet->setCellValue('A' . $row, $orderLabel->threePl?->name ?? '');
                $sheet->setCellValue('B' . $row, $orderLabel->code);
                $sheet->setCellValue('C' . $row, $orderLabel->printed_at ? 'Printed' : 'Not Printed');
                $sheet->setCellValue('D' . $row, $orderLabel->print_count ?? 0);
                $sheet->setCellValue('E' . $row, $orderLabel->order_date ? \Carbon\Carbon::parse($orderLabel->order_date)->format('d-m-Y') : '');
                $sheet->setCellValue('F' . $row, $orderLabel->page_number ?? '');

                // Add hyperlink with styling
                if ($pdfUrl) {
                    $sheet->setCellValue('G' . $row, 'Download PDF');
                    $sheet->getCell('G' . $row)->getHyperlink()->setUrl($pdfUrl);

                    // Style the hyperlink: blue, underline, bold
                    $linkStyle = $sheet->getStyle('G' . $row);
                    $linkStyle->getFont()
                        ->setUnderline(Font::UNDERLINE_SINGLE)
                        ->setBold(true)
                        ->getColor()->setARGB('FF0000FF');
                }

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $printInfo = $this->print_status === 'printed' ? '-printed' : ($this->print_status === 'not_printed' ? '-not-printed' : '');
            $filename = 'print-labels' . $printInfo . '-' . date('Y-m-d-His') . '.xlsx';

            logger('Creating Excel file: ' . $filename);

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            logger('Export error: ' . $e->getMessage());
            $this->error('Export failed: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Print Label" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>

    {{-- LIST VIEW --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400 dark:bg-gray-800/50 dark:text-gray-400" class="overflow-x-auto">
            {{-- Search box for Code --}}
            <div class="mb-4 flex items-center gap-2" x-data="{
                handlePrintPopup(event) {
                    console.log('Alpine print popup:', event.detail);
                    if (event.detail.url) {
                        window.open(event.detail.url, '_blank');
                    }
                }
            }"
            @open-print-popup.window="handlePrintPopup($event)">
            <x-input
                placeholder="Scan Here..."
                wire:model="searchInput"
                x-ref="searchBox"
                @keydown.enter="
                    $wire.searchAndPrint().then(() => {
                        setTimeout(() => {
                            $el.querySelector('input').focus();
                        }, 100);
                    });
                "
                icon="o-magnifying-glass"
                clearable
                class="max-w-xs"
            />
            <x-select
                placeholder="All Status"
                wire:model.live="print_status"
                :options="[
                    ['id' => 'not_printed', 'name' => 'Not Printed'],
                    ['id' => 'printed', 'name' => 'Printed']
                ]"
                icon="o-printer"
                class="max-w-xs"
            />
            <x-button
                label="Export to Excel"
                wire:click="export"
                spinner="export"
                icon="o-arrow-down-tray"
                class="btn-sm btn-success"
            />
            @if(!empty($code))
                <x-button
                    label="Clear"
                    wire:click="clearSearch"
                    icon="o-x-mark"
                    class="btn-sm btn-ghost"
                />
            @endif
        </div>

        <x-table :headers="$headers" :rows="$orderLabels" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>

            @scope('cell_platform', $orderLabel)
            @if($orderLabel->threePl && $orderLabel->threePl->name)
                <x-badge value="{{ $orderLabel->threePl->name }}" class="badge-primary badge-sm" />
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

            @scope('cell_print_status', $orderLabel)
            @if($orderLabel->printed_at)
                <div class="flex flex-col gap-1">
                    <x-badge value="Printed" class="badge-success badge-sm" />
                    <span class="text-xs text-gray-500">{{ $orderLabel->print_count }}x</span>
                </div>
            @else
                <x-badge value="Not Printed" class="badge-ghost badge-sm" />
            @endif
            @endscope

            @scope('cell_page_number', $orderLabel)
            @if($orderLabel->page_number)
                <x-badge value="{{ $orderLabel->page_number }}" class="badge-primary" />
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

            @scope('cell_file_download', $orderLabel)
            @if($orderLabel->file_path)
                <div class="flex gap-1">
                    {{-- Always show Download button --}}
                    <a href="{{ route('print-label.download', [
                        'path' => urlencode($orderLabel->file_path),
                        'page' => $orderLabel->page_number,
                        'label_id' => $orderLabel->id
                    ]) }}"
                       class="btn btn-xs btn-primary"
                       title="Download Page {{ $orderLabel->page_number }} - {{ $orderLabel->split_filename ?? basename($orderLabel->file_path) }}">
                        <x-icon name="o-arrow-down-tray" class="w-3 h-3" />
                        Download
                    </a>

                    {{-- Always show Print button --}}
                    <button
                        onclick="printLabel({{ $orderLabel->id }}, '{{ \Illuminate\Support\Facades\Storage::url($orderLabel->file_path) }}')"
                        class="btn btn-xs btn-outline btn-success"
                        title="Print PDF Page {{ $orderLabel->page_number }}">
                        <x-icon name="o-printer" class="w-3 h-3" />
                        Print
                    </button>
                </div>
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope
        </x-table>
    </x-card>

    {{-- Client-side library for fixing compressed PDF downloads --}}
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script>
        function printLabel(labelId, pdfUrl) {
            try {
                // Open PDF in new window for printing
                window.open(pdfUrl, '_blank');

                // Mark as printed via Livewire
                @this.markAsPrinted(labelId);

                // Refocus search box after short delay
                setTimeout(() => {
                    const searchBox = document.querySelector('[x-ref=searchBox] input');
                    if (searchBox) {
                        searchBox.focus();
                    }
                }, 100);
            } catch (error) {
                console.error('Print error:', error);
                alert('Failed to open print window. Please try again.');
            }
        }

        async function downloadPage(url, pageNum, filename, btnElement) {
            try {
                const originalText = btnElement.innerText;
                btnElement.innerText = 'Extracting...';
                btnElement.disabled = true;

                // Fetch the master PDF
                const existingPdfBytes = await fetch(url).then(res => res.arrayBuffer());

                // Load a PDFDocument from the existing PDF bytes
                const pdfDoc = await PDFLib.PDFDocument.load(existingPdfBytes);

                // Create a new PDFDocument
                const newPdfDoc = await PDFLib.PDFDocument.create();

                // Copy the specific page (0-indexed)
                const [copiedPage] = await newPdfDoc.copyPages(pdfDoc, [pageNum - 1]);
                newPdfDoc.addPage(copiedPage);

                // Serialize the PDFDocument to bytes (a Uint8Array)
                const pdfBytes = await newPdfDoc.save();

                // Trigger download
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                link.click();

                btnElement.innerText = 'Done!';
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.disabled = false;
                }, 2000);
            } catch (err) {
                console.error(err);
                alert('Failed to extract page. The PDF might be too complex for browser extraction.');
                btnElement.innerText = 'Error';
            }
        }

        // Listen for open-print-popup event from Livewire
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-print-popup', (data) => {
                console.log('Print popup event received:', data);
                // Handle both array format [{ url: '...' }] and object format { url: '...' }
                const url = Array.isArray(data) ? data[0]?.url : data?.url;
                if (url) {
                    console.log('Opening PDF:', url);
                    window.open(url, '_blank');
                } else {
                    console.error('No URL provided in event', data);
                    alert('Error: No PDF URL found');
                }
            });

            Livewire.on('download-batch-zip', () => {
                console.log('Download batch zip event received');
                window.location.href = '{{ route("print-label.download-batch-zip") }}';
            });
        });
    </script>

    <x-search-drawer>
        <x-grid>
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
            <x-input label="Batch No" wire:model="batch_no" />
            <x-input label="Code" wire:model="code" />
            <x-select label="Status" wire:model="status" :options="[
                ['id' => 'open', 'name' => 'Open'],
                ['id' => 'close', 'name' => 'Close'],
                ['id' => 'void', 'name' => 'Void']
            ]" placeholder="-- All --" />
            <x-select label="Print Status" wire:model="print_status" :options="[
                ['id' => 'not_printed', 'name' => 'Not Printed'],
                ['id' => 'printed', 'name' => 'Printed']
            ]" placeholder="-- All --" />
        </x-grid>
    </x-search-drawer>
</div>
