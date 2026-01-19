<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\StockAdjustmentIn;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'stock_adjustment_in_per_page')]
    public int $perPage = 10;

    #[Session(key: 'stock_adjustment_in_date1')]
    public string $date1 = '';

    #[Session(key: 'stock_adjustment_in_date2')]
    public string $date2 = '';

    #[Session(key: 'stock_adjustment_in_code')]
    public $code = '';

    #[Session(key: 'stock_adjustment_in_status')]
    public $status = '';

    public bool $drawer = false;
    public int $filterCount = 0;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view stock adjustment in');

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
            ['key' => 'code', 'label' => 'No. SAI', 'class' => 'truncate'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'products_count', 'label' => 'Products', 'class' => 'text-center', 'sortable' => false],
            ['key' => 'note', 'label' => 'Note', 'class' => 'max-w-[300px] truncate'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'truncate', 'format' => ['date', 'd/m/y, H:i']],
        ];
    }

    public function create(): void
    {
        $adjustment = StockAdjustmentIn::create([
            'code' => uniqid('SAI/'),
            'date' => now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('stock-adjustment-in.edit', $adjustment->id);
    }

    public function adjustments(): LengthAwarePaginator
    {
        return StockAdjustmentIn::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->withCount('details as products_count')
            ->with(['approvedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'adjustments' => $this->adjustments(),
            'statusOptions' => [
                ['id' => 'open', 'name' => 'Open'],
                ['id' => 'close', 'name' => 'Closed'],
                ['id' => 'void', 'name' => 'Voided'],
            ],
            'filterCount' => $this->filterCount,
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function clear(): void
    {
        $this->reset(['code', 'status']);
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');
        $this->resetPage();
        $this->success('Filter cleared.');
    }

    public function updateFilterCount(): void
    {
        $this->filterCount = collect([
            $this->code,
            $this->status,
        ])->filter(fn($value) => !empty($value))->count();
    }

}; ?>

<div>
    <x-header title="Stock Adjustment In" separator>
        <x-slot:subtitle>
            <x-button icon="o-funnel" @click="$wire.drawer = true" badge="{{ $filterCount }}" class="btn-sm" responsive />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('create stock adjustment in')
            <x-button label="Create" icon="o-plus" wire:click="create" spinner="create" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table
            :headers="$headers"
            :rows="$adjustments"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            :per-page-values="[10, 20, 50, 100]"
            link="/stock-adjustment-in/{id}/edit"
        >
            @scope('header_act', $header)
            @endscope

            @scope('cell_act', $adjustment)
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button icon="o-ellipsis-horizontal" class="btn-sm btn-ghost" />
                    </x-slot:trigger>
                    @can('update stock adjustment in')
                    <x-menu-item title="Edit" icon="o-pencil" link="{{ route('stock-adjustment-in.edit', $adjustment->id) }}" />
                    @endcan
                    @if($adjustment->status == 'open' && Gate::allows('approve stock adjustment in'))
                    <x-menu-item title="Approve" icon="o-check" link="{{ route('stock-adjustment-in.edit', $adjustment->id) }}" />
                    @endif
                    @if($adjustment->status == 'close' && Gate::allows('void stock adjustment in'))
                    <x-menu-item title="Void" icon="o-x-mark" link="{{ route('stock-adjustment-in.edit', $adjustment->id) }}" />
                    @endif
                </x-dropdown>
            @endscope

            @scope('cell_status', $adjustment)
                <x-status-badge :status="$adjustment->status" />
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filter" right separator with-close-button class="lg:w-1/3">
        <div class="space-y-4">
            <x-input label="Code" wire:model.live="code" icon="o-magnifying-glass" placeholder="Search code..." />

            <x-select label="Status" wire:model.live="status" :options="$statusOptions" placeholder="All Status" />

            <div class="grid grid-cols-2 gap-4">
                <x-input label="From Date" wire:model.live="date1" type="date" />
                <x-input label="To Date" wire:model.live="date2" type="date" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Clear" wire:click="clear" />
        </x-slot:actions>
    </x-drawer>
</div>
