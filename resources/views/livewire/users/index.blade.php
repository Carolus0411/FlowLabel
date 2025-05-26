<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
// use App\Models\Branch;
use App\Models\User;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'user_per_page')]
    public int $perPage = 10;

    #[Session(key: 'user_name')]
    public string $name = '';

    #[Session(key: 'user_email')]
    public string $email = '';

    #[Session(key: 'user_branch_id')]
    public string $branch_id = '';

    #[Session(key: 'user_role')]
    public string $role = '';

    #[Session(key: 'user_status')]
    public string $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public Collection $branchSearchable;

    public function mount(): void
    {
        Gate::authorize('view users');
        // $this->searchBranch();
    }

    public function headers(): array
    {
        return [
            ['key' => 'avatar', 'label' => 'Avatar', 'sortable' => false],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role', 'label' => 'Role', 'sortable' => false],
            // ['key' => 'branch.name', 'label' => 'Branch', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
        ];
    }

    public function users(): LengthAwarePaginator
    {
        $user = User::query()
        // ->with('branch')
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->filterLike('email', $this->email)
        // ->filterWhere('branch_id', $this->branch_id)
        ->filterWhere('status', $this->status);

        if ($this->role) {
            $user->role($this->role);
        }

        return $user->paginate($this->perPage);
    }

    public function with(): array
    {
        $this->branch_id = $this->branch_id ?? '';

        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
        ];
    }

    // public function searchBranch(string $value = ''): void
    // {
    //     $this->branchSearchable = Branch::query()
    //         ->filterWhere('name', $value)
    //         ->take(20)
    //         ->orderBy('name')
    //         ->get();
    // }

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
        $this->reset(['name','email','branch_id','role','status']);
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->name)) {
            $count++;
        }
        if (!empty($this->email)) {
            $count++;
        }
        if (!empty($this->branch_id)) {
            $count++;
        }
        if (!empty($this->role)) {
            $count++;
        }
        if (!empty($this->status)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function delete(User $user): void
    {
        Gate::authorize('delete users');
        $user->delete();
        $this->success("User has been deleted.");
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Users" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create users')
            <x-button label="Create" link="{{ route('users.create') }}" responsive icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_role', $user)
            {{ $user->getRoleNames()->join(', ') }}
            @endscope
            @scope('cell_avatar', $user)
            <x-avatar image="{{ $user->avatar ?? asset('assets/img/default-avatar.png') }}" class="!w-8" />
            @endscope
            @scope('cell_status', $user)
                <x-badge :value="$user->status->value" class="{{ $user->status->color() }}" />
            @endscope
            @scope('actions', $user)
            <div class="flex gap-1.5">
                @can('delete user')
                <x-button wire:click="delete({{ $user->id }})" spinner="delete({{ $user->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update user')
                <x-button link="{{ route('users.edit', $user->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
                <x-input label="Email" wire:model="email" />
                {{-- <x-choices label="Branch" wire:model="branch_id" :options="$branchSearchable" search-function="searchBranch" option-label="name" single searchable clearable placeholder="-- All --" /> --}}
                <x-select label="Role" wire:model="role" :options="\Spatie\Permission\Models\Role::get()" option-value="name" option-label="name" placeholder="-- All --" />
                <x-select label="Status" wire:model="status" :options="\App\Enums\ActiveStatus::toSelect()" placeholder="-- All --" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
