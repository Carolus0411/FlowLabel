<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;
    public $userId;
    
    public $name;
    public $email;
    public $password;
    public $status = 'active';
    public $selectedRoles = [];

    public function mount()
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function with()
    {
        return [
            'users' => User::with('roles')
                ->when($this->search, function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
            'roles' => Role::all(),
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->status = $user->status;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->userId ?? 'NULL'),
            'status' => 'required|in:active,inactive',
            'selectedRoles' => 'array',
        ];

        if (!$this->editMode || $this->password) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // Sync roles
        $user->syncRoles($this->selectedRoles);

        session()->flash('message', $this->editMode ? 'User updated successfully!' : 'User created successfully!');
        $this->closeModal();
        $this->resetPage();
    }

    public function delete($id)
    {
        if (auth()->id() === $id) {
            session()->flash('error', 'You cannot delete your own account!');
            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('message', 'User deleted successfully!');
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->status = 'active';
        $this->selectedRoles = [];
        $this->resetValidation();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            User Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session()->has('message'))
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('message') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-error mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <!-- Header Actions -->
                    <div class="flex justify-between items-center mb-4">
                        <div class="form-control w-full max-w-xs">
                            <input type="text" wire:model.live="search" placeholder="Search users..." class="input input-bordered w-full" />
                        </div>
                        <button wire:click="create" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add User
                        </button>
                    </div>

                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="avatar placeholder">
                                                    <div class="bg-neutral text-neutral-content rounded-full w-12">
                                                        <span class="text-xl">{{ substr($user->name, 0, 1) }}</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-bold">{{ $user->name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge badge-primary badge-sm mr-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-gray-400 text-sm">No roles</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-error' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button wire:click="edit({{ $user->id }})" class="btn btn-sm btn-ghost">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                @if (auth()->id() !== $user->id)
                                                    <button wire:click="delete({{ $user->id }})" wire:confirm="Are you sure you want to delete this user?" class="btn btn-sm btn-ghost text-error">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-gray-400">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if ($showModal)
        <div class="modal modal-open">
            <div class="modal-box max-w-2xl">
                <h3 class="font-bold text-lg mb-4">{{ $editMode ? 'Edit User' : 'Add New User' }}</h3>
                
                <form wire:submit="save">
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text">Name</span>
                        </label>
                        <input type="text" wire:model="name" class="input input-bordered w-full @error('name') input-error @enderror" />
                        @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" wire:model="email" class="input input-bordered w-full @error('email') input-error @enderror" />
                        @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text">Password {{ $editMode ? '(leave blank to keep current)' : '' }}</span>
                        </label>
                        <input type="password" wire:model="password" class="input input-bordered w-full @error('password') input-error @enderror" />
                        @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text">Status</span>
                        </label>
                        <select wire:model="status" class="select select-bordered w-full @error('status') select-error @enderror">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text">Roles</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($roles as $role)
                                <label class="label cursor-pointer justify-start">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}" class="checkbox checkbox-primary" />
                                    <span class="label-text ml-2">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedRoles') <span class="text-error text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="modal-action">
                        <button type="button" wire:click="closeModal" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">{{ $editMode ? 'Update' : 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
