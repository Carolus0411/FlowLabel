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

}; ?>
<div>
    <div class="px-10 lg:px-0 flex items-center justify-center min-h-dvh bg-gray-100 dark:bg-none dark:bg-neutral-900">
        <div class="w-md shadow">
            <div class="bg-white dark:bg-neutral-800 p-8 space-y-6">

                <x-auth-header :title="__('Thank You')" :description="__('Verification was successful.')" />

                <div class="space-x-1 text-center text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Your email has been verified.') }}</span>
                    <a href="{{ route('home') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Go to home') }}</a>.
                </div>
            </div>
        </div>
    </div>
</div>
