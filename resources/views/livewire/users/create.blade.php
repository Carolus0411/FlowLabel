<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Mary\Traits\Toast;
use App\Enums\Role;
// use App\Models\Branch;
use App\Models\User;

new class extends Component {
    use Toast, WithFileUploads;

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $avatar = '';
    public $branch_id = '';
    public $role = '';
    public $status = '';
    public Collection $branchSearchable;

    public function mount(): void
    {
        Gate::authorize('create users');
        // $this->searchBranch();
    }

    // public function searchBranch(string $value = ''): void
    // {
    //     $selectedOption = Branch::where('id', intval($this->branch_id))->get();
    //     $this->branchSearchable = Branch::query()
    //         ->where('name', 'like', "%$value%")
    //         ->active()
    //         ->take(20)
    //         ->orderBy('name')
    //         ->get()
    //         ->merge($selectedOption);
    // }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('users')],
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
            'avatar' => 'nullable|image|max:1024',
            'role' => 'required',
            // 'branch_id' => '',
            'status' => 'required',
        ]);

        unset($data['avatar']);
        unset($data['password_confirmation']);
        unset($data['role']);

        if ($this->avatar) {
            $url = $this->avatar->store('avatar', 'public');
            $data['avatar'] =  "/storage/".$url;
        }

        $data['password'] = Hash::make($data['password']);
        // $data['branch_id'] = intval($data['branch_id']);

        $user = User::create($data);

        $user->syncRoles([$this->role]);

        $this->success('User has been created.', redirectTo: route('users.index'));
    }
}; ?>

<div>
    <x-header title="Create User" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('users.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <div class="lg:w-[70%]">
        <x-form wire:submit="save">
            <x-card>
                <div class="space-y-4">
                    <x-file label="Avatar" wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                        <img src="{{ $user->avatar ?? asset('assets/img/default-avatar.png') }}" class="h-40 rounded-lg" />
                    </x-file>
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" />
                        <x-input label="Email" wire:model="email" />
                    </div>
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                        <x-input label="Password" wire:model="password" type="password" />
                        <x-input label="Confirm Password" wire:model="password_confirmation" type="password" />
                    </div>
                    {{-- <x-choices label="Branch" wire:model.live="branch_id" :options="$branchSearchable" search-function="searchBranch" option-label="name" single searchable clearable /> --}}
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                        <x-select label="Role" :options="\Spatie\Permission\Models\Role::get()" wire:model="role" option-value="name" option-label="name" placeholder="-- Select --" />
                        <x-select label="Status" :options="\App\Enums\ActiveStatus::toSelect()" wire:model="status" placeholder="-- Select --" />
                    </div>
                </div>
            </x-card>
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('users.index') }}" />
                <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </div>
</div>
