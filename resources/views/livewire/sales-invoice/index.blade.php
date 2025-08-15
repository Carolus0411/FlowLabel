<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\SalesInvoice;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'salesinvoice_per_page')]
    public int $perPage = 10;

    #[Session(key: 'salesinvoice_date1')]
    public string $date1 = '';

    #[Session(key: 'salesinvoice_date2')]
    public string $date2 = '';

    #[Session(key: 'salesinvoice_code')]
    public string $code = '';

    #[Session(key: 'salesinvoice_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view sales-invoice');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'payment_status', 'label' => 'Payment'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'invoice_date', 'label' => 'Invoice Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'contact.name', 'label' => 'Customer', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'dpp_amount', 'label' => 'DPP Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'ppn.value', 'label' => 'PPN %', 'class' => 'text-right', 'sortable' => false],
            ['key' => 'ppn_amount', 'label' => 'PPN', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'pph.value', 'label' => 'PPH %', 'class' => 'text-right', 'sortable' => false],
            ['key' => 'pph_amount', 'label' => 'PPH', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'stamp_amount', 'label' => 'Stamp', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'invoice_amount', 'label' => 'Invoice Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            // ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        $salesInvoice = SalesInvoice::create([
            'code' => uniqid(),
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'status' => 'open',
        ]);

        $this->redirectRoute('sales-invoice.edit', $salesInvoice->id);
    }

    public function salesInvoices(): LengthAwarePaginator
    {
        return SalesInvoice::stored()
            ->whereDateBetween('DATE(invoice_date)', $this->date1, $this->date2)
            ->with(['contact','ppn','pph'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'salesInvoices' => $this->salesInvoices(),
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function search(): void
    {
        $data = $this->validate([
            'date1' => 'required|date',
            'date2' => 'required|date|after_or_equal:date1',
            'code' => 'nullable',
        ]);
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['code','status']);
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function delete(SalesInvoice $salesInvoice): void
    {
        Gate::authorize('delete sales-invoice');
        $salesInvoice->delete();
        $this->success('Invoice has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export sales-invoice');

        $salesInvoice = SalesInvoice::stored()
        ->whereDateBetween('DATE(invoice_date)', $this->date1, $this->date2)
        ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Sales Invoice.xlsx');
        foreach ( $salesInvoice->lazy() as $salesInvoice ) {
            $writer->addRow([
                'id' => $salesInvoice->id ?? '',
                'code' => $salesInvoice->code ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Sales Invoice.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Sales Invoice" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export sales-invoice')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import sales-invoice')
            <x-button label="Import" link="{{ route('sales-invoice.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create sales-invoice')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$salesInvoices" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('sales-invoice.edit', ['salesInvoice' => '[id]'])">
            @scope('cell_act', $salesInvoice)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('sales-invoice.edit', $salesInvoice->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Show Journal" onclick="popupWindow('{{ route('print.journal', ['SalesInvoice', base64_encode($salesInvoice->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-magnifying-glass" />
            </x-dropdown>
            @endscope
            {{-- @scope('cell_contact.name', $salesInvoice)
            <div class="whitespace-nowrap">{{ $salesInvoice->contact->name }}</div>
            @endscope --}}
            @scope('cell_status', $salesInvoice)
            <x-status-badge :status="$salesInvoice->status" />
            @endscope
            @scope('cell_payment_status', $salesInvoice)
            @if ($salesInvoice->status == 'close')
            <x-payment-status-badge :data="$salesInvoice" />
            @endif
            @endscope
            {{-- @scope('actions', $salesInvoice)
            <div class="flex gap-1.5">
                @can('delete sales invoice')
                <x-button wire: click="delete({{ $salesInvoice->id }})" spinner="delete({{ $salesInvoice->id }})" wire: confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update sales invoice')
                <x-button link="{{ route('sales-invoice.edit', $salesInvoice->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope --}}
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <x-grid>
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
            <x-input label="Code" wire:model="code" />
            <x-select label="Status" wire:model="status" :options="\App\Enums\Status::toSelect()" placeholder="-- All --" />
        </x-grid>
    </x-search-drawer>
</div>
