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
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false],
            ['key' => 'debit_total', 'label' => 'Debit Total', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'credit_total', 'label' => 'Credit Total', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'ref_name', 'label' => 'Ref Name'],
            ['key' => 'ref_id', 'label' => 'Ref ID'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'format' => ['date', 'd-M-y, H:i']],
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
            ->with(['contact','updatedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
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
        $this->reset(['code']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) {
            $count++;
        }
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
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import journal')
            <x-button label="Import" link="{{ route('journal.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create journal')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$journals" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('journal.edit', ['journal' => '[id]'])">
            @scope('cell_status', $journal)
            @if ($journal->status == 'close')
            <x-badge value="Closed" class="text-xs badge-success" />
            @elseif ($journal->status == 'void')
            <x-badge value="Void" class="text-xs badge-error" />
            @else
            <x-badge value="Open" class="text-xs badge-primary" />
            @endif
            @endscope
            {{-- @scope('actions', $journal)
            <div class="flex gap-1.5">
                @can('delete journal')
                <x-button wire: click="delete({{ $journal->id }})" spinner="delete({{ $journal->id }})" wire: confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update journal')
                <x-button link="{{ route('journal.edit', $journal->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope --}}
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-datetime label="Start Date" wire:model="date1" />
                    <x-datetime label="End Date" wire:model="date2" />
                </div>
                <x-input label="Code" wire:model="code" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
