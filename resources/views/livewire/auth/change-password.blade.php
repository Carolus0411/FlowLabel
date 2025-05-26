<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;
use App\Rules\CurrentPassword;
use App\Models\User;

new
#[Layout('site.layouts.app')]
class extends Component {
    use Toast;

    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    public function mount(): void
    {

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
        </x-auth-section>

    </div>
</div>
