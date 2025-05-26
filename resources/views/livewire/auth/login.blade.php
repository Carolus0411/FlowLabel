<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        // It is logged in
        if (auth()->check()) {
            return redirect()->intended('/');
        }

        if (app()->environment('local')) {
            $this->email = 'admin@gmail.com';
            $this->password = 'q1w2e3r4';
            $this->remember = true;
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>
<div>
    <div class="px-10 lg:px-0 flex items-center justify-center min-h-dvh bg-gray-100 bg-cover bg-no-repeat bg-center bg-[url(../img/bg.jpg)] dark:bg-none dark:bg-neutral-900 ">
        <div class="w-md shadow">
            <div class="bg-white dark:bg-neutral-800 p-8 space-y-6">

                <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

                <x-auth-alert :status="session('status')" class="alert-success" />

                <x-form wire:submit="login">
                    <x-input label="E-mail" wire:model="email" icon="o-envelope" placeholder="email@example.com" wire:loading.attr="disabled" wire:target="login" />
                    <x-input label="Password" wire:model="password" type="password" icon="o-key" placeholder="Password" wire:loading.attr="disabled" wire:target="login" />

                    <div class="space-y-4 my-4">
                        <x-checkbox label="Remember me" wire:model="remember" />
                        <x-button label="Login" type="submit" icon="o-paper-airplane" class="btn-primary w-full" spinner="login" />
                    </div>

                    @if (Route::has('register'))
                        <div class="space-x-1 text-center text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Don\'t have an account?') }}</span>
                            <a href="{{  route('register') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Sign up') }}</a>
                        </div>
                    @endif

                    @if (Route::has('password.request'))
                        <div class="space-x-1 text-center text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Forgot your password?') }}</span>
                            <a href="{{  route('password.request') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Reset password') }}</a>
                        </div>
                    @endif

                </x-form>
            </div>
        </div>
    </div>
</div>
