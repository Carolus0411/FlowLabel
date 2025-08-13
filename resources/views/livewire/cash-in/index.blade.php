<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\CashIn;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'cashin_per_page')]
    public int $perPage = 10;

    #[Session(key: 'cashin_date1')]
    public $date1 = '';

    #[Session(key: 'cashin_date2')]
    public $date2 = '';

    #[Session(key: 'cashin_code')]
    public $code = '';

    #[Session(key: 'cashin_type')]
    public $type = '';

    #[Session(key: 'cashin_status')]
    public $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view cash-in');

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
            ['key' => 'code', 'label' => 'Code', 'class' => 'truncate'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false, 'class' => 'max-w-[300px] truncate'],
            ['key' => 'total_amount', 'label' => 'Total Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'type', 'label' => 'Type', 'class' => 'truncate'],
            ['key' => 'note', 'label' => 'Note', 'class' => 'max-w-[300px] truncate'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'truncate', 'format' => ['date', 'd-m-y, H:i']],
        ];
    }

    public function create(): void
    {
        $cashIn = CashIn::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('cash-in.edit', $cashIn->id);
    }

    public function cashIns(): LengthAwarePaginator
    {
        return cashIn::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['contact'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('type', $this->type)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'cashIns' => $this->cashIns(),
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
            'date1' => 'required',
            'date2' => 'required',
        ]);
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['code','type','status']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->type)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function export()
    {
        Gate::authorize('export cash-in');

        $cashIn = cashIn::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('CashIn.xlsx');
        foreach ( $cashIn->lazy() as $cashIn ) {
            $writer->addRow([
                'id' => $cashIn->id,
                'code' => $cashIn->code,
                'date' => $cashIn->date,
                'note' => $cashIn->note,
                'cash_account_id' => $cashIn->cash_account_id,
                'contact_id' => $cashIn->contact_id,
                'total_amount' => $cashIn->total_amount,
                'type' => $cashIn->type,
                'status' => $cashIn->status,
                'saved' => $cashIn->saved,
                'created_by' => $cashIn->created_by,
                'updated_by' => $cashIn->updated_by,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'CashIn.xlsx');
    }
}; ?>
<div>
    {{-- HEADER --}}
    <x-header title="Cash In" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export cash-in')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            @can('import cash-in')
            <x-button label="Import" link="{{ route('cash-in.import') }}" icon="o-arrow-up-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create cash-in')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$cashIns"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('cash-in.edit', ['cashIn' => '[id]'])"
        >
            @scope('cell_act', $cashIn)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('cash-in.edit', $cashIn->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Show Journal" onclick="popupWindow('{{ route('print.journal', ['CashIn', base64_encode($cashIn->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-magnifying-glass" />
                <x-menu-item title="Print" onclick="popupWindow('{{ route('print.cash-in', $cashIn->id) }}', 'cash-in', '1000', '560', 'yes', 'center')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $cashIn)
            <x-status-badge :status="$cashIn->status" />
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
        </div>
        <x-input label="Code" wire:model="code" />
        <x-select label="Type" wire:model="type" :options="\App\Enums\IncomeType::toSelect()" placeholder="-- All --" />
        <x-select label="Status" wire:model="status" :options="\App\Enums\Status::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>
