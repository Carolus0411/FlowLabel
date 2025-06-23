<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Bank;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'bank_per_page')]
    public int $perPage = 10;

    #[Session(key: 'bank_name')]
    public string $name = '';

    #[Session(key: 'bank_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view bank');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function banks(): LengthAwarePaginator
    {
        return Bank::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'banks' => $this->banks(),
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
            'name' => 'nullable',
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
        if (!empty($this->name)) $count++;
        if (!empty($this->is_active)) $count++;
        $this->filterCount = $count;
    }

    public function delete(Bank $bank): void
    {
        Gate::authorize('delete bank');
        $bank->delete();
        $this->success('Bank has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export bank');

        $bank = Bank::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Bank.xlsx');
        foreach ( $bank->lazy() as $bank ) {
            $writer->addRow([
                'id' => $bank->id ?? '',
                'name' => $bank->name ?? '',
                'is_active' => $bank->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Bank.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Bank" separator progress-indicator>
        <x-slot:actions>
            @can('export bank')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import bank')
            <x-button label="Import" link="{{ route('bank.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create bank')
            <x-button label="Create" link="{{ route('bank.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$banks" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $bank)
            <x-active-badge :status="$bank->is_active" />
            @endscope
            @scope('actions', $bank)
            <div class="flex gap-1.5">
                @can('delete bank')
                <x-button wire:click="delete({{ $bank->id }})" spinner="delete({{ $bank->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update bank')
                <x-button link="{{ route('bank.edit', $bank->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <x-input label="Name" wire:model="name" />
        <x-select label="Active" wire:model="is_active" :options="\App\Enums\ActiveStatus::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>
