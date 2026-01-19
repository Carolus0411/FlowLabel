<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Journal;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'journal_per_page')]
    public int $perPage = 10;

    #[Session(key: 'journal_date1')]
    public string $date1 = '';

    #[Session(key: 'journal_date2')]
    public string $date2 = '';

    #[Session(key: 'journal_code')]
    public string $code = '';

    #[Session(key: 'journal_ref_name')]
    public string $ref_name = '';

    #[Session(key: 'journal_ref_id')]
    public string $ref_id = '';

    #[Session(key: 'journal_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view journal');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'action', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'truncate max-w-[300px]'],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false, 'class' => 'truncate max-w-[300px]'],
            ['key' => 'debit_total', 'label' => 'Debit Total', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'credit_total', 'label' => 'Credit Total', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'ref_name', 'label' => 'Ref Name', 'class' => 'truncate max-w-[200px]'],
            ['key' => 'ref_id', 'label' => 'Ref ID', 'class' => 'truncate max-w-[200px]'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'format' => ['date', 'd-M-y, H:i'], 'class' => 'truncate'],
            ['key' => 'updatedBy.name', 'label' => 'Updated By'],
        ];
    }

    public function create(): void
    {
        $journal = Journal::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('journal.edit', $journal->id);
    }

    public function journals(): LengthAwarePaginator
    {
        return Journal::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['contact','supplier','updatedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->filterWhere('ref_name', $this->ref_name)
            ->filterLike('ref_id', $this->ref_id)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'journals' => $this->journals(),
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
        $this->reset(['code','status','ref_name','ref_id']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->ref_name)) $count++;
        if (!empty($this->ref_id)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function export()
    {
        Gate::authorize('export journal');

        $journal = Journal::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Journal.xlsx');
        foreach ( $journal->lazy() as $journal ) {
            $writer->addRow([
                'id' => $journal->id,
                'code' => $journal->code,
                'date' => $journal->date,
                'note' => $journal->note,
                'type' => $journal->type,
                'contact_id' => $journal->contact_id,
                'ref_name' => $journal->ref_name,
                'ref_id' => $journal->ref_id,
                'debit_total' => $journal->debit_total,
                'credit_total' => $journal->credit_total,
                'status' => $journal->status,
                'saved' => $journal->saved,
                'created_by' => $journal->created_by,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Journal.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Journal" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export journal')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            @can('import journal')
            <x-button label="Import" link="{{ route('journal.import') }}" icon="o-arrow-up-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create journal')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$journals" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('journal.edit', ['journal' => '[id]'])">
            @scope('cell_action', $journal)
            <x-dropdown class="btn-xs btn-ghost">
                <x-menu-item title="Edit" link="{{ route('journal.edit', $journal->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Print" onclick="popupWindow('{{ route('print.journal', ['journal', $journal->id]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $journal)
            <x-status-badge :status="$journal->status" />
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
            <x-input label="Ref Name" wire:model="ref_name" />
            <x-input label="Ref ID" wire:model="ref_id" />
        </x-grid>
    </x-search-drawer>
</div>
