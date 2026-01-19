<?php

use App\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;

new class extends Component {
    use WithPagination;

    #[Url]
    public $date1;

    #[Url]
    public $date2;

    #[Url]
    public $code;

    #[Url]
    public $status;

    public $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public $drawer = false;

    public $filterCount = 0;

    public function mount()
    {
        $this->date1 = now()->startOfMonth()->format('Y-m-d H:i');
        $this->date2 = now()->endOfMonth()->format('Y-m-d H:i');
        $this->updateFilterCount();
    }

    public function getDeliveryOrdersProperty(): LengthAwarePaginator
    {
        return DeliveryOrder::query()
            ->with(['contact'])
            ->when($this->code, fn(Builder $q) => $q->where('code', 'like', "%$this->code%"))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->whereDateBetween('DATE(delivery_date)', $this->date1, $this->date2)
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => 'Act', 'sortable' => false],
            ['key' => 'code', 'label' => 'Code', 'sortable' => true],
            ['key' => 'delivery_date', 'label' => 'Date', 'sortable' => true],
            ['key' => 'sales_order_code', 'label' => 'SO Code', 'sortable' => true],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
    }

    public function clear(): void
    {
        $this->reset(['code', 'status']);
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != 'page') {
            $this->resetPage();
        }
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
        Gate::authorize('export delivery-order');

        if (! Schema::hasTable('delivery_order')) {
            $this->error('Database table `delivery_order` does not exist. Please run migrations.');
            return null;
        }

        $query = DeliveryOrder::stored()
            ->whereDateBetween('DATE(delivery_date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Delivery Order.xlsx');
        foreach ($query->lazy() as $delivery) {
            $writer->addRow([
                'id' => $delivery->id ?? '',
                'code' => $delivery->code ?? '',
                'delivery_date' => $delivery->delivery_date ?? '',
                'sales_order_code' => $delivery->sales_order_code ?? '',
                'total_qty' => $delivery->total_qty ?? 0,
                'total_amount' => $delivery->total_amount ?? 0,
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'Delivery Order.xlsx');
    }
}; ?>

<div>
    <x-header title="Delivery Order" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export delivery-order')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create delivery-order')
            <x-button label="Create" link="{{ route('delivery-order.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$this->headers()" :rows="$this->deliveryOrders" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('delivery-order.edit', ['deliveryOrder' => '[id]'])">
            @scope('cell_act', $delivery)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('delivery-order.edit', $delivery->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.delivery-order', $delivery->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $delivery)
            <x-status-badge :status="$delivery->status" />
            @endscope
            @scope('cell_contact.name', $delivery)
            <div class="whitespace-nowrap">{{ $delivery->contact->name }}</div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="space-y-4">
            <x-datetime label="Start Date" wire:model.live.debounce="date1" icon="o-calendar" />
            <x-datetime label="End Date" wire:model.live.debounce="date2" icon="o-calendar" />
            <x-input label="Code" wire:model.live.debounce="code" icon="o-magnifying-glass" />
            <x-select label="Status" wire:model.live="status" :options="[['id' => '','name' => 'All'],['id' => 'open','name' => 'Open'],['id' => 'close','name' => 'Close'],['id' => 'void','name' => 'Void']]" />
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
