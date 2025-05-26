<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Mary\Traits\Toast;
use App\Rules\CurrentPassword;
use App\Models\User;

new
#[Layout('site.layouts.app')]
class extends Component {
    use Toast, WithFileUploads;

    public $name = '';
    public $email = '';
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    public $avatar = '';
    public $storedAvatar = '';
    // public $role = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->storedAvatar = auth()->user()->avatar;
        // $this->role = auth()->user()->getRoleNames()->first();
        $this->avatar = '';
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore(auth()->user()->id)],
            'avatar' => 'nullable|image|max:1024',
        ]);

        unset($data['avatar']);

        if ($this->avatar) {
            $url = $this->avatar->store('avatar', 'public');
            $data['avatar'] =  "/storage/".$url;
        }

        auth()->user()->update($data);

        $this->success('Profile has been updated.', redirectTo: route('profile'), position: 'toast-top toast-end');
    }

    public function changePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', new CurrentPassword],
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        unset($validated['current_password']);
        unset($validated['password_confirmation']);

        $validated['password'] = Hash::make($validated['password']);

        auth()->user()->update($validated);

        $this->success('Password has been updated.', position: 'toast-top toast-end');
    }
}; ?>
<div>
    <x-site-nav />

    <div class="max-w-screen-lg mx-auto px-5 xl:px-0 pt-10">

        <x-auth-section>
            <x-slot:sidebar>
                <x-auth-menu />
            </x-slot:sidebar>

            <x-form wire:submit="save">
                <x-card separator>
                    <div class="space-y-4">
                        <x-file label="Avatar" wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                            <img src="{{ $storedAvatar ?? asset('assets/img/default-avatar.png') }}" class="h-40 rounded-lg" />
                        </x-file>
                        <x-input label="Name" wire:model="name" />
                        <x-input label="Email" wire:model="email" />
                    </div>
                    <x-slot:actions>
                        <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                    </x-slot:actions>
                </x-card>
            </x-form>
        </x-auth-section>

        {{-- <div class="drawer lg:drawer-open z-50">
            <input id="my-drawer-1" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content p-6">
                <label for="my-drawer-1" class="btn btn-primary drawer-button lg:hidden w-full mb-10">
                    User Menu
                </label>
                <x-form wire:submit="save">
                    <x-card separator>
                        <div class="space-y-4">
                            <x-file label="Avatar" wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                                <img src="{{ $storedAvatar ?? asset('assets/img/default-avatar.png') }}" class="h-40 rounded-lg" />
                            </x-file>
                            <x-input label="Name" wire:model="name" />
                            <x-input label="Email" wire:model="email" />
                        </div>
                        <x-slot:actions>
                            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                        </x-slot:actions>
                    </x-card>
                </x-form>
            </div>
            <div class="drawer-side">
                <label for="my-drawer-1" aria-label="close sidebar" class="drawer-overlay"></label>
                <ul class="menu w-[250px] bg-base-200 lg:bg-none text-base-content min-h-screen lg:min-h-auto p-6 gap-2">
                <li><a class="menu-active">Profile</a></li>
                <li><a>Change Password</a></li>
                <li><a>Log Out</a></li>
                </ul>
            </div>
        </div> --}}

        {{-- <x-header title="User Profile" separator />
        <div class="xl:w-[60%]">
            <div class="space-y-6 ">
                <x-form wire:submit="save">
                    <x-card separator>
                        <div class="space-y-4">
                            <x-file label="Avatar" wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                                <img src="{{ $storedAvatar ?? asset('assets/img/default-avatar.png') }}" class="h-40 rounded-lg" />
                            </x-file>
                            {{-- <div class="space-y-4 xl:space-y-0 xl:grid grid-cols-2 gap-4"> -- }}
                                <x-input label="Name" wire:model="name" />
                                <x-input label="Email" wire:model="email" />
                                {{-- <x-input label="Role" wire:model="role" readonly /> -- }}
                            {{-- </div> -- }}
                        </div>
                        <x-slot:actions>
                            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                        </x-slot:actions>
                    </x-card>
                </x-form>

                <x-form wire:submit="changePassword">
                    <x-card title="Change Password" separator>
                        <div class="space-y-4">
                            <x-input label="Current Password" wire:model="current_password" type="password" />
                            <x-input label="Password" wire:model="password" type="password" />
                            <x-input label="Confirm Password" wire:model="password_confirmation" type="password" />
                        </div>
                        <x-slot:actions>
                            <x-button label="Save" icon="o-paper-airplane" spinner="changePassword" type="submit" class="btn-primary" />
                        </x-slot:actions>
                    </x-card>
                </x-form>
            </div>
        </div> --}}

    </div>
</div>
