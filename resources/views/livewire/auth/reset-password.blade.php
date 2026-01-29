<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PasswordReset) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>
<div>
    <div class="px-10 lg:px-0 flex items-center justify-center min-h-dvh bg-gray-100 dark:bg-none dark:bg-neutral-900 ">
        <div class="w-md shadow">
            <div class="bg-white dark:bg-neutral-800 p-8 space-y-6">

                <x-auth-header :title="__('Reset Password')" :description="__('Please enter your new password below')" />

                <x-form wire:submit="resetPassword">
                    <x-input label="E-mail address" wire:model="email" icon="o-envelope" placeholder="email@example.com" wire:loading.attr="disabled" wire:target="resetPassword" />
                    <x-input label="Password" wire:model="password" type="password" icon="o-key" placeholder="Password" wire:loading.attr="disabled" wire:target="resetPassword" />
                    <x-input label="Confirm password" wire:model="password_confirmation" type="password" icon="o-key" placeholder="Confirm password" wire:loading.attr="disabled" wire:target="resetPassword" />

                    <div class="space-y-4 mt-4">
                        <x-button label="Reset Password" type="submit" class="btn-primary w-full" spinner="resetPassword" />
                    </div>
                </x-form>
            </div>
        </div>
    </div>
</div>
