<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\PurchaseInvoice;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'purchaseinvoice_per_page')]
    public int $perPage = 10;

    #[Session(key: 'purchaseinvoice_date1')]
    public string $date1 = '';

    #[Session(key: 'purchaseinvoice_date2')]
    public string $date2 = '';

    #[Session(key: 'purchaseinvoice_code')]
    public string $code = '';

    #[Session(key: 'purchaseinvoice_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view purchase-invoice');

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
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'dpp_amount', 'label' => 'DPP Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'invoice_amount', 'label' => 'Invoice Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        $purchaseInvoice = PurchaseInvoice::create([
            'code' => uniqid(),
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'invoice_type' => 'AP',
            'status' => 'open',
        ]);

        $this->redirectRoute('purchase-invoice.edit', $purchaseInvoice->id);
    }

    public function purchaseInvoices(): LengthAwarePaginator
    {
        return PurchaseInvoice::stored()
            ->whereDateBetween('DATE(invoice_date)', $this->date1, $this->date2)
            ->with(['supplier'])
            ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'purchaseInvoices' => $this->purchaseInvoices(),
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

    public function export()
    {
        Gate::authorize('export purchase-invoice');

        $query = PurchaseInvoice::stored()
            ->whereDateBetween('DATE(invoice_date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Purchase Invoice.xlsx');
        foreach ($query->lazy() as $invoice) {
            $writer->addRow([
                'id' => $invoice->id ?? '',
                'code' => $invoice->code ?? '',
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'Purchase Invoice.xlsx');
    }
}; ?>

<div>
    <x-header title="Purchase Invoice" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export purchase-invoice')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import purchase-invoice')
            <x-button label="Import" link="{{ route('purchase-invoice.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create purchase-invoice')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$purchaseInvoices" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('purchase-invoice.edit', ['purchaseInvoice' => '[id]'])">
            @scope('cell_act', $invoice)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('purchase-invoice.edit', $invoice->id) }}" icon="o-pencil-square" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $invoice)
            <x-status-badge :status="$invoice->status" />
            @endscope
            @scope('cell_payment_status', $invoice)
            @if ($invoice->status == 'close')
            <x-payment-status-badge :data="$invoice" />
            @endif
            @endscope
            @scope('cell_supplier.name', $invoice)
            <div class="whitespace-nowrap">{{ $invoice->supplier->name }}</div>
            @endscope
        </x-table>
    </x-card>

    <x-search-drawer>
        <x-grid>
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
            <x-input label="Code" wire:model="code" />
            <x-select label="Status" wire:model="status" :options="\App\Enums\Status::toSelect()" placeholder="-- All --" />
        </x-grid>
    </x-search-drawer>
</div>
