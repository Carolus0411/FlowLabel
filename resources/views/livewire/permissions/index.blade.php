<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Permission as MyPermission;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'permission_per_page')]
    public int $perPage = 10;

    #[Session(key: 'permission_name')]
    public string $name = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view permissions');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'resource', 'label' => 'Resource', 'sortable' => false],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'guard_name', 'label' => 'Guard'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function permissions(): LengthAwarePaginator
    {
        $permission = Permission::query()
        ->orderBy('resource')
        ->orderBy(...array_values($this->sortBy));

        if (!empty($this->name)) {
            $permission->where('name', 'like', '%'.$this->name.'%');
        }

        return $permission->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'permissions' => $this->permissions(),
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
        $this->filterCount = $count;
    }

    public function delete(MyPermission $permission): void
    {
        Gate::authorize('delete permissions');
        $permission->delete();
        $this->warning("Permission has been deleted");
    }

    public function export()
    {
        Gate::authorize('export permissions');

        $permissions = Permission::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Permissions.xlsx');
        foreach ( $permissions->lazy() as $permission ) {
            $writer->addRow([
                'id' => $permission->id ?? '',
                'resource' => $permission->resource ?? '',
                'name' => $permission->name ?? '',
                'guard' => $permission->guard ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Permissions.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Permissions" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            <x-button label="Import" link="{{ route('permissions.import') }}" icon="o-arrow-up-tray" />
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            <x-button label="Create" link="{{ route('permissions.create') }}" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- TABLE  --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$permissions" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('actions', $permission)
            <div class="flex gap-1.5">
                @can('delete permissions')
                <x-button wire:click="delete({{ $permission->id }})" spinner="delete({{ $permission->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update user')
                <x-button link="{{ route('permissions.edit', $permission->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-5">
                <x-input label="Name" wire:model="name" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
