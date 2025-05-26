<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Ppn;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'ppn_per_page')]
    public int $perPage = 10;

    #[Session(key: 'ppn_name')]
    public string $name = '';

    #[Session(key: 'ppn_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public array $activeList;

    public function mount(): void
    {
        Gate::authorize('view ppn');
        $this->updateFilterCount();

        $this->activeList = [
            ['id' => 'active', 'name' => 'Active'],
            ['id' => 'inactive', 'name' => 'Inactive']
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'value', 'label' => 'Value'],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function ppns(): LengthAwarePaginator
    {
        return Ppn::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'ppns' => $this->ppns(),
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
        if (!empty($this->name)) {
            $count++;
        }
        if (!empty($this->is_active)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function delete(Ppn $ppn): void
    {
        Gate::authorize('delete ppn');
        $ppn->delete();
        $this->success('Ppn has been deleted.');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="PPN" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create ppn')
            <x-button label="Create" link="{{ route('ppn.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$ppns" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $ppn)
            @if ($ppn->is_active)
            <x-badge value="Active" class="text-xs uppercase badge-success badge-soft" />
            @else
            <x-badge value="Inactive" class="text-xs uppercase badge-error badge-soft" />
            @endif
            @endscope
            @scope('actions', $ppn)
            <div class="flex gap-1.5">
                @can('delete ppn')
                <x-button wire:click="delete({{ $ppn->id }})" spinner="delete({{ $ppn->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update ppn')
                <x-button link="{{ route('ppn.edit', $ppn->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Name" wire:model="name" />
                <x-select label="Active" wire:model="is_active" :options="$activeList" placeholder="-- All --" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
