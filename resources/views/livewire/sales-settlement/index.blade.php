<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\SalesSettlement;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'salessettlement_per_page')]
    public int $perPage = 10;

    #[Session(key: 'salessettlement_date1')]
    public string $date1 = '';

    #[Session(key: 'salessettlement_date2')]
    public string $date2 = '';

    #[Session(key: 'salessettlement_code')]
    public string $code = '';

    #[Session(key: 'salessettlement_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view salessettlement');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code', 'class' => 'truncate'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y'], 'class' => 'truncate'],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false, 'class' => 'truncate max-w-[300px]'],
            ['key' => 'source_amount', 'label' => 'Source Amount', 'format' => ['currency', '2.,', '']],
            ['key' => 'paid_amount', 'label' => 'Paid Amount', 'format' => ['currency', '2.,', '']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'format' => ['date', 'd-M-y, H:i'], 'class' => 'truncate'],
            ['key' => 'updatedBy.name', 'label' => 'Updated By', 'class' => 'truncate'],
        ];
    }

    public function create(): void
    {
        $salesSettlement = SalesSettlement::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('sales-settlement.edit', $salesSettlement->id);
    }

    public function salesSettlements(): LengthAwarePaginator
    {
        return SalesSettlement::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['contact','updatedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'salesSettlements' => $this->salesSettlements(),
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
        $this->drawer = false;
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
        Gate::authorize('export salessettlement');

        $salessettlement = SalesSettlement::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('SalesSettlement.xlsx');
        foreach ( $salessettlement->lazy() as $salessettlement ) {
            $writer->addRow([
                'id' => $salessettlement->id,
                'code' => $salessettlement->code,
                'date' => $salessettlement->date,
                'note' => $salessettlement->note,
                'contact_id' => $salessettlement->contact_id,
                'total_amount' => $salessettlement->total_amount,
                'status' => $salessettlement->status,
                'saved' => $salessettlement->saved,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'SalesSettlement.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Sales Settlement" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export sales-settlement')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            @can('import sales-settlement')
            <x-button label="Import" link="{{ route('sales-settlement.import') }}" icon="o-arrow-up-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create sales-settlement')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$salesSettlements" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('sales-settlement.edit', ['salesSettlement' => '[id]'])">
            @scope('cell_status', $salesSettlement)
            <x-status-badge :status="$salesSettlement->status" />
            @endscope
            {{-- @scope('actions', $sales-settlement)
            <div class="flex gap-1.5">
                @can('delete salessettlement')
                <x-button wire: click="delete({{ $salessettlement->id }})" spinner="delete({{ $salessettlement->id }})" wire: confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update salessettlement')
                <x-button link="{{ route('salessettlement.edit', $salessettlement->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
