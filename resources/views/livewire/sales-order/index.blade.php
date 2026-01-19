<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\SalesOrder;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'salesorder_per_page')]
    public int $perPage = 10;

    #[Session(key: 'salesorder_date1')]
    public string $date1 = '';

    #[Session(key: 'salesorder_date2')]
    public string $date2 = '';

    #[Session(key: 'salesorder_code')]
    public string $code = '';

    #[Session(key: 'salesorder_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        // Gate::authorize('view sales-order'); // Commented out until permissions are created

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
            ['key' => 'do_status', 'label' => 'DO Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'order_date', 'label' => 'Order Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'dpp_amount', 'label' => 'DPP Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'order_amount', 'label' => 'Order Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        if (! Schema::hasTable('sales_order')) {
            $this->error('Database table `sales_order` does not exist. Please run migrations.');
            return;
        }

        $salesOrder = SalesOrder::create([
            'code' => uniqid(),
            'order_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'invoice_type' => 'SO',
            'status' => 'open',
            'saved' => 0, // Mark as draft until properly saved
        ]);

        $this->redirectRoute('sales-order.edit', $salesOrder->id);
    }

    public function salesOrders(): LengthAwarePaginator
    {
        if (! Schema::hasTable('sales_order')) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        return SalesOrder::stored()
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
            ->with(['contact', 'deliveryOrders'])
            ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'salesOrders' => $this->salesOrders(),
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

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['code','status']);
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
        $this->filterCount = $count;
    }

    public function clearFilter(): void
    {
        $this->code = '';
        $this->status = '';
        $this->updateFilterCount();
    }

    public function export(): void
    {
        $rows = [];
        $salesOrders = SalesOrder::stored()
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
            ->with(['contact', 'deliveryOrders'])
            ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->get();

        foreach ($salesOrders as $salesOrder) {
            $rows[] = [
                'Code' => $salesOrder->code,
                'Date' => $salesOrder->order_date,
                'Contact' => $salesOrder->contact->name ?? '',
                'DO Status' => $salesOrder->do_status,
                'Amount' => $salesOrder->order_amount,
                'Status' => $salesOrder->status,
            ];
        }

        $writer = SimpleExcelWriter::streamDownload('sales-orders.xlsx')
            ->addRows($rows);

        $writer->toBrowser();
    }
}; ?>

<div>
    <x-header title="Sales Order" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            <x-button label="Import" link="{{ route('sales-order.import') }}" icon="o-arrow-up-tray" />
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$salesOrders" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('sales-order.edit', ['salesOrder' => '[id]'])">
            @scope('cell_act', $salesOrder)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('sales-order.edit', $salesOrder->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.sales-order', $salesOrder->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $salesOrder)
            <x-status-badge :status="$salesOrder->status" />
            @endscope
            @scope('cell_do_status', $salesOrder)
            <x-status-badge :status="$salesOrder->do_status" />
            @endscope
            @scope('cell_contact.name', $salesOrder)
            <div class="whitespace-nowrap">{{ $salesOrder->contact->name }}</div>
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
