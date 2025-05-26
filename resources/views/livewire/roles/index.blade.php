<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Role as MyRole;
use Spatie\Permission\Models\Role;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'role_per_page')]
    public int $perPage = 10;

    #[Session(key: 'role_name')]
    public string $name = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view roles');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Role Name'],
            ['key' => 'guard_name', 'label' => 'Guard'],
            ['key' => 'permissions', 'label' => 'Permissions', 'sortable' => false],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function roles(): LengthAwarePaginator
    {
        $role = Role::query()
        ->with('permissions')
        ->orderBy(...array_values($this->sortBy));

        if (!empty($this->name)) {
            $role->where('name', 'like', '%'.$this->name.'%');
        }

        return $role->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'roles' => $this->roles(),
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

    public function delete(MyRole $role): void
    {
        Gate::authorize('delete roles');
        $role->delete();
        $this->warning("Role has been deleted");
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Roles" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create roles')
            <x-button label="Create" link="{{ route('roles.create') }}" responsive icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE  --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$roles" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_permissions', $role)
            @php
            $permissions = $role->getAllPermissions();
            @endphp
            <div class="flex flex-wrap gap-2">
            @foreach ( $permissions as $permission )
            <x-badge :value="$permission->name" class="bg-sky-50 dark:bg-sky-950" />
            @endforeach
            </div>
            @endscope
            @scope('actions', $role)
            <div class="flex gap-1.5">
                @can('delete roles')
                <x-button wire:click="delete({{ $role->id }})" spinner="delete({{ $role->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update user')
                <x-button link="{{ route('roles.edit', $role->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
