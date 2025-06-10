<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\CashBook;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'cashbook_per_page')]
    public int $perPage = 10;

    #[Session(key: 'cashbook_code')]
    public string $code = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public $journalCode = '';

    public function mount(): void
    {
        Gate::authorize('view cash-book');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'contact.name', 'label' => 'Contact', 'sortable' => false],
            ['key' => 'total_amount', 'label' => 'Total Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'note', 'label' => 'Note'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        $cashBook = CashBook::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('cash-book.edit', $cashBook->id);
    }

    public function cashBooks(): LengthAwarePaginator
    {
        return CashBook::stored()
            ->with(['contact'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'cashBooks' => $this->cashBooks(),
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
            'code' => 'nullable',
        ]);
    }

    public function clear(): void
    {
        $this->success('Filters cleared.');
        $this->reset();
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function showJournal($code): void
    {
        $this->journalCode = $code;
        $this->dispatch('show-journal');
    }

    public function export()
    {
        Gate::authorize('export cash-book');

        $cashBook = CashBook::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('CashBook.xlsx');
        foreach ( $cashBook->lazy() as $cashBook ) {
            $writer->addRow([
                'id' => $cashBook->id,
                'code' => $cashBook->code,
                'date' => $cashBook->date,
                'note' => $cashBook->note,
                'type' => $cashBook->type,
                'contact_id' => $cashBook->contact_id,
                'total_amount' => $cashBook->total_amount,
                'status' => $cashBook->status,
                'saved' => $cashBook->saved,
                'created_by' => $cashBook->created_by,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'CashBook.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Cash Book" separator progress-indicator>
        <x-slot:actions>
            @can('export cash-book')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import cash-book')
            <x-button label="Import" link="{{ route('cash-book.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create cash-book')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$cashBooks" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('cash-book.edit', ['cashBook' => '[id]'])">
            @scope('cell_act', $cashBook)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('cash-book.edit', $cashBook->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Show Journal" wire:click="showJournal('{{ $cashBook->code }}')" icon="o-magnifying-glass" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $cashBook)
            @if ($cashBook->status == 'close')
            <x-badge value="Closed" class="text-xs badge-success" />
            @elseif ($cashBook->status == 'void')
            <x-badge value="Void" class="text-xs badge-error" />
            @else
            <x-badge value="Open" class="text-xs badge-primary" />
            @endif
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Code" wire:model="code" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>

    <livewire:journal.modal :ref_id="$journalCode" ref_name="CashBook">
</div>
