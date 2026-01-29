<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>
<div>
    <div class="px-10 lg:px-0 flex items-center justify-center min-h-dvh bg-gray-100 dark:bg-none dark:bg-neutral-900 ">
        <div class="w-md shadow">

            <div class="bg-white dark:bg-neutral-800 p-8 space-y-10">

                <x-auth-header :title="__('Re-send Email Verification')" :description="__('Please verify your email address by clicking on the link we just emailed to you.')" />

                @if (session('status') == 'verification-link-sent')
                    <x-auth-alert :status="__('A new verification link has been sent to the email address you provided during registration.')" class="alert-success" />
                @endif

                <div class="flex flex-col items-center justify-between space-y-3">
                    <x-button :label="__('Resend verification email')" wire:click="sendVerification" spinner="sendVerification" class="w-full btn btn-primary" />
                    {{-- <div class="divider">Or</div><x-button : label="__('Log out')" wire: click="logout" spinner="logout" class="w-full" /> --}}
                </div>

                <div class="space-x-1 text-center text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Back to') }}</span>
                    <a href="{{ route('home') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Home') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
