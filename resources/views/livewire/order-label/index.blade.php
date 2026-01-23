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

    #[Session(key: 'orderlabel_per_page')]
    public int $perPage = 10;

    #[Session(key: 'orderlabel_date1')]
    public string $date1 = '';

    #[Session(key: 'orderlabel_date2')]
    public string $date2 = '';

    #[Session(key: 'orderlabel_code')]
    public string $code = '';

    public string $searchInput = '';

    #[Session(key: 'orderlabel_batch_no')]
    public string $batch_no = '';

    #[Session(key: 'orderlabel_status')]
    public string $status = '';

    #[Session(key: 'orderlabel_print_status')]
    public string $print_status = 'not_printed';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public array $selectedItems = [];

    #[Session(key: 'orderlabel_view_mode')]
    public string $viewMode = 'grouped'; // 'grouped' or 'list'

    public function updated($property): void
    {
        if (in_array($property, ['code', 'batch_no', 'status', 'print_status'])) {
            $this->updateFilterCount();
            $this->resetPage();
        }
    }

    public function mount(): void
    {
        // Gate::authorize('view order-label'); // Commented out until permissions are created

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'select', 'label' => '', 'sortable' => false, 'disableLink' => true],
            ['key' => 'batch_no', 'label' => 'Batch No', 'sortable' => true, 'class' => 'whitespace-nowrap'],
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

        $this->redirectRoute('order-label.edit', $orderLabel->id);
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
            ->when(!empty($this->batch_no), fn($q) => $q->where('batch_no', 'like', '%' . $this->batch_no . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->when($this->print_status === 'printed', fn($q) => $q->whereNotNull('printed_at'))
            ->when($this->print_status === 'not_printed', fn($q) => $q->whereNull('printed_at'))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function batches()
    {
        if (! Schema::hasTable('order_label')) {
            return collect([]);
        }

        // Batch statistics are not affected by print_status or status filters
        // Only date and batch_no filters apply
        $query = OrderLabel::query()
            ->selectRaw('batch_no,
                three_pl_id,
                COUNT(*) as total_pages,
                MIN(order_date) as order_date,
                MAX(original_filename) as original_filename,
                SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_count,
                MAX(created_at) as import_date')
            ->with('threePl')
            ->where('saved', 1)
            ->whereNotNull('batch_no')
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
            ->when(!empty($this->batch_no), fn($q) => $q->where('batch_no', 'like', '%' . $this->batch_no . '%'))
            ->groupBy('batch_no', 'three_pl_id')
            ->orderBy('import_date', 'desc');

        return $query->get();
    }

    public function with(): array
    {
        return [
            'orderLabels' => $this->orderLabels(),
            'batches' => $this->batches(),
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
        if (!empty($this->batch_no)) $count++;
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

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grouped' ? 'list' : 'grouped';
    }

    public function viewBatchDetail(string $batchNo): void
    {
        $this->batch_no = $batchNo;
        $this->viewMode = 'list';
        $this->resetPage();
    }

    public function backToAllBatches(): void
    {
        $this->batch_no = '';
        $this->viewMode = 'grouped';
        $this->resetPage();
    }

    public function downloadBatch(string $batchNo): void
    {
        $orderLabels = OrderLabel::where('batch_no', $batchNo)->get();

        if ($orderLabels->isEmpty()) {
            $this->warning('No files found for this batch.');
            return;
        }

        // Sanitize batch number for filename
        $safeBatchNo = str_replace(['/', '\\', ' '], '-', $batchNo);
        $zipFileName = 'batch_' . $safeBatchNo . '_' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        $filesAdded = 0;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($orderLabels as $orderLabel) {
                if ($orderLabel->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($orderLabel->file_path)) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($orderLabel->file_path);
                    $fileName = $orderLabel->split_filename ?? basename($orderLabel->file_path);

                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $fileName);
                        $filesAdded++;
                    }
                }
            }
            $zip->close();

            if ($filesAdded === 0) {
                @unlink($zipPath);
                $this->warning('No valid PDF files found for this batch.');
                return;
            }

            // Store zip info in session for download route
            session(['batch_download_zip' => $zipPath]);
            session(['batch_download_filename' => $zipFileName]);

            $this->success($filesAdded . ' file(s) ready for download.');

            // Trigger download via JavaScript
            $this->dispatch('download-batch-zip');
        } else {
            $this->error('Failed to create zip file. Please check folder permissions.');
        }
    }

    public function downloadSelected()
    {
        if (empty($this->selectedItems)) {
            $this->warning('No items selected.');
            return;
        }

        $orderLabels = OrderLabel::whereIn('id', $this->selectedItems)->get();

        if ($orderLabels->isEmpty()) {
            $this->warning('Selected items not found.');
            return;
        }

        // Create a zip file with all selected PDFs
        $zipFileName = 'order-labels-' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        $filesAdded = 0;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($orderLabels as $orderLabel) {
                if ($orderLabel->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($orderLabel->file_path)) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($orderLabel->file_path);
                    $fileName = $orderLabel->split_filename ?? basename($orderLabel->file_path);

                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $fileName);
                        $filesAdded++;
                    }
                }
            }
            $zip->close();

            if ($filesAdded === 0) {
                @unlink($zipPath);
                $this->warning('No valid PDF files found for selected items.');
                return;
            }

            $this->success($filesAdded . ' file(s) added to download.');
            $this->selectedItems = [];

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } else {
            $this->error('Failed to create zip file.');
        }
    }

    public function export()
    {
        try {
            logger('Export started');

            $orderLabels = OrderLabel::stored()
                ->with('threePl')
                ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
                ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
                ->when(!empty($this->batch_no), fn($q) => $q->where('batch_no', 'like', '%' . $this->batch_no . '%'))
                ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
                ->when($this->print_status === 'printed', fn($q) => $q->whereNotNull('printed_at'))
                ->when($this->print_status === 'not_printed', fn($q) => $q->whereNull('printed_at'))
                ->orderBy('batch_no')
                ->orderBy('page_number')
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
            $headers = ['Batch No', 'Platform', 'Code', 'Print Status', 'Print Count', 'Order Date', 'Page', 'PDF Link'];
            $sheet->fromArray($headers, null, 'A1');

            // Style header row
            $headerStyle = $sheet->getStyle('A1:H1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            // Add data rows
            $row = 2;
            foreach ($orderLabels as $orderLabel) {
                // Generate public download URL (no auth required)
                $pdfUrl = '';
                if ($orderLabel->file_path) {
                    $pdfUrl = route('order-label.public-download', [
                        'path' => urlencode($orderLabel->file_path),
                        'page' => $orderLabel->page_number,
                        'label_id' => $orderLabel->id
                    ]);
                }

                $sheet->setCellValue('A' . $row, $orderLabel->batch_no ?? '');
                $sheet->setCellValue('B' . $row, $orderLabel->threePl?->name ?? '');
                $sheet->setCellValue('C' . $row, $orderLabel->code);
                $sheet->setCellValue('D' . $row, $orderLabel->printed_at ? 'Printed' : 'Not Printed');
                $sheet->setCellValue('E' . $row, $orderLabel->print_count ?? 0);
                $sheet->setCellValue('F' . $row, $orderLabel->order_date ? \Carbon\Carbon::parse($orderLabel->order_date)->format('d-m-Y') : '');
                $sheet->setCellValue('G' . $row, $orderLabel->page_number ?? '');

                // Add hyperlink with styling
                if ($pdfUrl) {
                    $sheet->setCellValue('H' . $row, 'Download PDF');
                    $sheet->getCell('H' . $row)->getHyperlink()->setUrl($pdfUrl);

                    // Style the hyperlink: blue, underline, bold
                    $linkStyle = $sheet->getStyle('H' . $row);
                    $linkStyle->getFont()
                        ->setUnderline(Font::UNDERLINE_SINGLE)
                        ->setBold(true)
                        ->getColor()->setARGB('FF0000FF');
                }

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $batchInfo = !empty($this->batch_no) ? '-batch-' . str_replace('/', '-', $this->batch_no) : '';
            $printInfo = $this->print_status === 'printed' ? '-printed' : ($this->print_status === 'not_printed' ? '-not-printed' : '');
            $filename = 'order-labels' . $batchInfo . $printInfo . '-' . date('Y-m-d-His') . '.xlsx';

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
    <x-header title="Order Label" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Dashboard" link="{{ route('order-label.dashboard') }}" icon="o-chart-bar" class="btn-outline" />
            <x-button label="Download Selected" wire:click="downloadSelected" spinner="downloadSelected" icon="o-arrow-down-tray" class="btn-success" :disabled="count($selectedItems) === 0" />
            <x-button label="Import PDF" link="{{ route('order-label.import') }}"
                     icon="o-document-arrow-up" class="btn-primary" />
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>

    @if($viewMode === 'grouped')
        {{-- GROUPED VIEW BY BATCH --}}
        <div class="space-y-4">
            @forelse($batches as $batch)
                <x-card class="hover:shadow-lg transition-shadow border-l-4 border-blue-500">
                    {{-- Header with badges and info - responsive flex wrap --}}
                    <div class="flex flex-wrap items-start gap-3 mb-4">
                        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[300px]">
                            <div>
                                <x-badge value="{{ $batch->batch_no }}" class="badge-info badge-md font-mono" />
                            </div>
                            @if($batch->threePl && $batch->threePl->name)
                            <div>
                                <x-badge value="{{ $batch->threePl->name }}" class="badge-primary badge-md" />
                            </div>
                            @endif
                            <div class="border-l pl-3">
                                <div class="text-xs text-gray-500">Import Date</div>
                                <div class="font-semibold text-sm text-gray-700">{{ \Carbon\Carbon::parse($batch->import_date)->format('d-M-Y H:i') }}</div>
                            </div>
                            <div class="border-l pl-3">
                                <div class="text-xs text-gray-500">Original File</div>
                                <div class="font-medium text-sm max-w-[200px] lg:max-w-[300px] truncate text-gray-700" title="{{ $batch->original_filename }}">
                                    {{ $batch->original_filename }}
                                </div>
                            </div>
                        </div>

                        {{-- Stats and Actions - separate row on small screens --}}
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto lg:ml-auto">
                            {{-- Statistics --}}
                            <div class="flex items-center gap-2 lg:gap-4">
                                <div class="text-center px-2">
                                    <div class="text-2xl lg:text-3xl font-bold text-blue-600">{{ $batch->total_pages }}</div>
                                    <div class="text-xs text-gray-500 uppercase">Pages</div>
                                </div>
                                <div class="text-center px-2 border-l">
                                    <div class="text-2xl lg:text-3xl font-bold text-green-600">{{ $batch->printed_count }}</div>
                                    <div class="text-xs text-gray-500 uppercase">Printed</div>
                                </div>
                                <div class="text-center px-2 border-l">
                                    <div class="text-2xl lg:text-3xl font-bold text-orange-600">{{ $batch->total_pages - $batch->printed_count }}</div>
                                    <div class="text-xs text-gray-500 uppercase">Pending</div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex gap-2 border-l pl-3">
                                <x-button
                                    label="Download"
                                    wire:click="downloadBatch('{{ $batch->batch_no }}')"
                                    icon="o-arrow-down-tray"
                                    spinner="downloadBatch"
                                    class="btn-xs lg:btn-sm btn-success"
                                />
                                <x-button
                                    label="Details"
                                    wire:click="viewBatchDetail('{{ $batch->batch_no }}')"
                                    icon="o-eye"
                                    class="btn-xs lg:btn-sm btn-primary"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-3">
                        @php
                            $percentage = $batch->total_pages > 0 ? round(($batch->printed_count / $batch->total_pages) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-gray-600">Print Progress:</span>
                            <span class="text-xs font-bold text-gray-700">{{ $percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <div class="flex items-center gap-1">
                            <x-icon name="o-calendar" class="w-4 h-4" />
                            <span>Order: {{ \Carbon\Carbon::parse($batch->order_date)->format('d-M-Y') }}</span>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div class="text-center py-8 text-gray-500">
                        <x-icon name="o-inbox" class="w-16 h-16 mx-auto mb-2 text-gray-300" />
                        <p>No batches found for the selected filters.</p>
                    </div>
                </x-card>
            @endforelse
        </div>
    @else
        {{-- LIST VIEW (EXISTING) --}}
        <x-card wire:loading.class="bg-slate-200/50 text-slate-400 dark:bg-gray-800/50 dark:text-gray-400" class="overflow-x-auto">
            @if(!empty($batch_no))
                <div class="mb-4 p-3 dark:bg-gray-800 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-funnel" class="w-5 h-5 text-black-600" />
                        <span class="text-sm text-black-800">
                            Viewing batch: <strong class="font-mono">{{ $batch_no }}</strong>
                        </span>
                    </div>
                    <x-button
                        label="Back to All Batches"
                        wire:click="backToAllBatches"
                        icon="o-arrow-left"
                        class="btn-xs btn-primary"
                    />
                </div>
            @endif

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
                placeholder="Search by Code..."
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
            @scope('cell_select', $orderLabel)
            <x-checkbox wire:model.live="selectedItems" :value="$orderLabel->id" />
            @endscope

            @scope('cell_batch_no', $orderLabel)
            @if($orderLabel->batch_no)
                <x-badge value="{{ $orderLabel->batch_no }}" class="badge-info badge-sm font-mono" />
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

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
                    <a href="{{ route('order-label.download', [
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
    @endif

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
                window.location.href = '{{ route("order-label.download-batch-zip") }}';
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
