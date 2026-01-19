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
use App\Models\PurchaseReceival;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'purchasereceival_per_page')]
    public int $perPage = 10;

    #[Session(key: 'purchasereceival_date1')]
    public string $date1 = '';

    #[Session(key: 'purchasereceival_date2')]
    public string $date2 = '';

    #[Session(key: 'purchasereceival_code')]
    public string $code = '';

    #[Session(key: 'purchasereceival_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view purchase-receival');

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
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'receival_date', 'label' => 'Receival Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'purchase_order_code', 'label' => 'PO Code'],
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'total_qty', 'label' => 'Total Qty', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'whitespace-nowrap', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function purchaseReceivals(): LengthAwarePaginator
    {
        if (! Schema::hasTable('purchase_receival')) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        return PurchaseReceival::stored()
            ->whereDateBetween('DATE(receival_date)', $this->date1, $this->date2)
            ->with(['supplier', 'purchaseOrder'])
            ->when(!empty($this->code), fn($q) => $q->where('code', 'like', '%' . $this->code . '%'))
            ->when(!empty($this->status), fn($q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'purchaseReceivals' => $this->purchaseReceivals(),
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
        Gate::authorize('export purchase-receival');

        if (! Schema::hasTable('purchase_receival')) {
            $this->error('Database table `purchase_receival` does not exist. Please run migrations.');
            return null;
        }

        $query = PurchaseReceival::stored()
            ->whereDateBetween('DATE(receival_date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Purchase Receival.xlsx');
        foreach ($query->lazy() as $receival) {
            $writer->addRow([
                'id' => $receival->id ?? '',
                'code' => $receival->code ?? '',
                'receival_date' => $receival->receival_date ?? '',
                'purchase_order_code' => $receival->purchase_order_code ?? '',
                'total_qty' => $receival->total_qty ?? 0,
                'total_amount' => $receival->total_amount ?? 0,
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'Purchase Receival.xlsx');
    }
}; ?>

<div>
    <x-header title="Purchase Receival" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export purchase-receival')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create purchase-receival')
            <x-button label="Create" link="{{ route('purchase-receival.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$purchaseReceivals" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('purchase-receival.edit', ['purchaseReceival' => '[id]'])">
            @scope('cell_act', $receival)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('purchase-receival.edit', $receival->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.purchase-receival', $receival->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $receival)
            <x-status-badge :status="$receival->status" />
            @endscope
            @scope('cell_supplier.name', $receival)
            <div class="whitespace-nowrap">{{ $receival->supplier->name }}</div>
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
