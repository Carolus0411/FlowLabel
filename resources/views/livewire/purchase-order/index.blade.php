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
use App\Models\PurchaseOrder;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'purchaseorder_per_page')]
    public int $perPage = 10;

    #[Session(key: 'purchaseorder_date1')]
    public string $date1 = '';

    #[Session(key: 'purchaseorder_date2')]
    public string $date2 = '';

    #[Session(key: 'purchaseorder_code')]
    public string $code = '';

    #[Session(key: 'purchaseorder_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view purchase-order');

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
            ['key' => 'payment_status', 'label' => 'Received'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'order_date', 'label' => 'Order Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'dpp_amount', 'label' => 'DPP Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'order_amount', 'label' => 'Order Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        if (! Schema::hasTable('purchase_order')) {
            $this->error('Database table `purchase_order` does not exist. Please run migrations.');
            return;
        }

        $purchaseOrder = PurchaseOrder::create([
            'code' => uniqid(),
            'order_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'invoice_type' => 'AP',
            'status' => 'open',
            'saved' => 0, // Mark as draft until properly saved
        ]);

        $this->redirectRoute('purchase-order.edit', $purchaseOrder->id);
    }

    public function purchaseOrders(): LengthAwarePaginator
    {
        if (! Schema::hasTable('purchase_order')) {
            // Return an empty paginator when the DB table doesn't exist (useful for test env before migrations)
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        return PurchaseOrder::stored()
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
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
            'purchaseOrders' => $this->purchaseOrders(),
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
        Gate::authorize('export purchase-order');

        if (! Schema::hasTable('purchase_order')) {
            $this->error('Database table `purchase_order` does not exist. Please run migrations.');
            return null;
        }

        $query = PurchaseOrder::stored()
            ->whereDateBetween('DATE(order_date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Purchase Order.xlsx');
        foreach ($query->lazy() as $order) {
            $writer->addRow([
                'id' => $order->id ?? '',
                'code' => $order->code ?? '',
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'Purchase Order.xlsx');
    }
}; ?>

<div>
    <x-header title="Purchase Order" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export purchase-order')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import purchase-order')
            <x-button label="Import" link="{{ route('purchase-order.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create purchase-order')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$purchaseOrders" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('purchase-order.edit', ['purchaseOrder' => '[id]'])">
            @scope('cell_act', $order)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('purchase-order.edit', $order->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.purchase-order', $order->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $order)
            <x-status-badge :status="$order->status" />
            @endscope
            @scope('cell_payment_status', $order)
            @if ($order->status == 'close')
                @php
                    $receivalStatus = $order->receival_status;
                @endphp
                @if ($receivalStatus == 'received')
                    <span class="badge badge-success badge-sm">Received</span>
                @elseif ($receivalStatus == 'partial')
                    <span class="badge badge-warning badge-sm">Partial</span>
                @else
                    <span class="badge badge-ghost badge-sm">Pending</span>
                @endif
            @endif
            @endscope
            @scope('cell_supplier.name', $order)
            <div class="whitespace-nowrap">{{ $order->supplier->name }}</div>
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

