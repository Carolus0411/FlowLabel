<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\SalesInvoiceDirect;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'salesinvoicedirect_per_page')]
    public int $perPage = 10;

    #[Session(key: 'salesinvoicedirect_date1')]
    public string $date1 = '';

    #[Session(key: 'salesinvoicedirect_date2')]
    public string $date2 = '';

    #[Session(key: 'salesinvoicedirect_code')]
    public string $code = '';

    #[Session(key: 'salesinvoicedirect_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view sales-invoice-direct');

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
        $salesInvoiceDirect = SalesInvoiceDirect::create([
            'code' => uniqid(),
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'status' => 'open',
        ]);

        $this->redirectRoute('sales-invoice-direct.edit', $salesInvoiceDirect->id);
    }

    public function salesInvoices(): LengthAwarePaginator
    {
        return SalesInvoiceDirect::stored()
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

    public function delete(SalesInvoiceDirect $salesInvoiceDirect): void
    {
        Gate::authorize('delete sales-invoice-direct');
        $salesInvoiceDirect->delete();
        $this->success('Invoice has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export sales-invoice-direct');

        $salesInvoiceDirect = SalesInvoiceDirect::stored()
        ->whereDateBetween('DATE(invoice_date)', $this->date1, $this->date2)
        ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Sales Invoice Direct.xlsx');
        foreach ( $salesInvoiceDirect->lazy() as $salesInvoice ) {
            $writer->addRow([
                'id' => $salesInvoice->id ?? '',
                'code' => $salesInvoice->code ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Sales Invoice Direct.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Sales Invoice Direct" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export sales-invoice-direct')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import sales-invoice-direct')
            <x-button label="Import" link="{{ route('sales-invoice-direct.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create sales-invoice-direct')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$salesInvoices" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('sales-invoice-direct.edit', ['salesInvoiceDirect' => '[id]'])">
            @scope('cell_act', $salesInvoice)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('sales-invoice-direct.edit', $salesInvoice->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.sales-invoice-direct', $salesInvoice->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
                <x-menu-item title="Show Journal" onclick="popupWindow('{{ route('print.journal', ['SalesInvoiceDirect', base64_encode($salesInvoice->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-magnifying-glass" />
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
