<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Carbon\Carbon;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\SimpleExcel\SimpleExcelWriter;
use App\Models\PurchaseSettlement;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'purchasesettlement_per_page')]
    public int $perPage = 10;
    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public string $date1 = '';
    public string $date2 = '';
    public string $code = '';
    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('view purchase-settlement');

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
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'max-w-[300px]'],
            ['key' => 'source_amount', 'label' => 'Source Amount', 'format' => ['currency', '2.,', '']],
            ['key' => 'paid_amount', 'label' => 'Paid Amount', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'format' => ['date', 'd-M-y, H:i'], 'class' => 'truncate'],
        ];
    }

    public function create(): void
    {
        $purchaseSettlement = \App\Models\PurchaseSettlement::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('purchase-settlement.edit', $purchaseSettlement->id);
    }

    public function purchaseSettlements(): LengthAwarePaginator
    {
        return PurchaseSettlement::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['supplier','updatedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'purchaseSettlements' => $this->purchaseSettlements(),
        ];
    }

    // updated() is defined later and will handle resetting pagination/filter count.

    public function export()
    {
        Gate::authorize('export purchase-settlement');

        $purchaseSettlements = PurchaseSettlement::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Purchase Settlement.xlsx');
        foreach ( $purchaseSettlements->lazy() as $purchaseSettlement ) {
            $writer->addRow([
                'id' => $purchaseSettlement->id,
                'code' => $purchaseSettlement->code,
                'date' => $purchaseSettlement->date,
                'note' => $purchaseSettlement->note,
                'supplier_id' => $purchaseSettlement->supplier_id,
                'total_amount' => $purchaseSettlement->total_amount ?? 0,
                'status' => $purchaseSettlement->status,
                'saved' => $purchaseSettlement->saved,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Purchase Settlement.xlsx');
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
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }
};
?>

<div>
    {{-- HEADER --}}
    <x-header title="Purchase Settlement" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export purchase-settlement')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            @can('import purchase-settlement')
            <x-button label="Import" link="{{ route('purchase-settlement.import') }}" icon="o-arrow-up-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create purchase-settlement')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$purchaseSettlements" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('purchase-settlement.edit', ['purchaseSettlement' => '[id]'])">
            @scope('cell_act', $purchaseSettlement)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('purchase-settlement.edit', $purchaseSettlement->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" @click="window.open('{{ route('print.purchase-settlement', $purchaseSettlement->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $purchaseSettlement)
            <x-status-badge :status="$purchaseSettlement->status" />
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
