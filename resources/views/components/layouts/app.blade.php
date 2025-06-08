<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/favicon/favicon.svg') }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    {{-- Flatpickr  --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

    {{-- EasyMDE --}}
    {{-- <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css"> --}}
    {{-- <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script> --}}

    {{-- Chart.js  --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-inter antialiased bg-base-200 dark:bg-base-200">

    {{-- The navbar with `sticky` and `full-width` --}}
    <x-nav id="nav" sticky full-width class="h-[65px] z-50">

        <x-slot:brand>
            {{-- Drawer toggle for "main-drawer" --}}
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>

            {{-- Brand --}}
            <x-app-brand />
        </x-slot:brand>

        {{-- Right side actions --}}
        <x-slot:actions>
            <div class="flex items-center gap-0.5 py-1">
                <x-theme-toggle class="btn btn-ghost btn-sm" />
                @if($user = auth()->user())
                {{-- <div wire:key="notification-navbar">
                    <livewire:notification.menu lazy />
                </div> --}}
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button class="btn-ghost btn-sm" responsive>
                            <x-avatar :title="\Illuminate\Support\Str::limit($user->name, 20)" image="{{ $user->avatar ?? asset('assets/img/default-avatar.png') }}" class="!h-6" />
                        </x-button>
                    </x-slot:trigger>
                    <x-menu-item title="My Profile" link="{{ route('users.profile') }}" />
                    <x-menu-separator />
                    <x-menu-item title="Log Out" link="{{ route('users.logout') }}" no-wire-navigate />
                </x-dropdown>
                @endif
            </div>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main with-nav full-width>

        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" class="bg-base-100 lg:bg-white lg:border-r lg:border-gray-200 dark:lg:bg-inherit dark:lg:border-none">

            {{-- MENU --}}
            <x-menu activate-by-route class="text-[13px] font-light">

                <x-menu-item title="Home" icon="o-sparkles" link="{{ route('dashboard') }}" />

                <x-menu-sub title="Sales" icon="o-shopping-cart">
                    <x-menu-item title="Invoice" link="{{ route('sales-invoice.index') }}" :hidden="auth()->user()->cannot('view sales invoice')" />
                </x-menu-sub>

                <x-menu-sub title="General Ledger" icon="o-clipboard-document-list">
                    <x-menu-item title="Journal" link="{{ route('journal.index') }}" :hidden="auth()->user()->cannot('view journal')" />
                </x-menu-sub>

                <x-menu-sub title="Master" icon="o-circle-stack">
                    <x-menu-item title="Contact" link="{{ route('contact.index') }}" :hidden="auth()->user()->cannot('view contact')" />
                    <x-menu-item title="PPN" link="{{ route('ppn.index') }}" :hidden="auth()->user()->cannot('view ppn')" />
                    <x-menu-item title="PPH" link="{{ route('pph.index') }}" :hidden="auth()->user()->cannot('view pph')" />
                    <x-menu-item title="Chart Of Account" link="{{ route('coa.index') }}" :hidden="auth()->user()->cannot('view coa')" />
                    <x-menu-item title="Service Charge" link="{{ route('service-charge.index') }}" :hidden="auth()->user()->cannot('view service charge')" />
                    <x-menu-item title="Currency" link="{{ route('currency.index') }}" :hidden="auth()->user()->cannot('view currency')" />
                    <x-menu-item title="Uom" link="{{ route('uom.index') }}" :hidden="auth()->user()->cannot('view uom')" />
                    <x-menu-sub title="Cash And Bank">
                        <x-menu-item title="Bank" link="{{ route('bank.index') }}" :hidden="auth()->user()->cannot('view bank')" />
                        <x-menu-item title="Bank Account" link="{{ route('bank-account.index') }}" :hidden="auth()->user()->cannot('view bank-account')" />
                    </x-menu-sub>
                </x-menu-sub>

                <x-menu-sub title="Setup" icon="o-cog-6-tooth">
                    <x-menu-item title="Settings" link="{{ route('setting.general') }}" :hidden="auth()->user()->cannot('view general setting')" />
                    <x-menu-item title="Account Mapping" link="{{ route('setting.account-mapping') }}" :hidden="auth()->user()->cannot('view account mapping')" />
                    <x-menu-item title="Send Test Mail" link="{{ route('mail.test') }}" :hidden="auth()->user()->cannot('send test mail')" />
                </x-menu-sub>

                <x-menu-sub title="Users" icon="o-users">
                    <x-menu-item title="Users" link="{{ route('users.index') }}" :hidden="auth()->user()->cannot('view users')" />
                    <x-menu-item title="Roles" link="{{ route('roles.index') }}" :hidden="auth()->user()->cannot('view roles')" />
                    <x-menu-item title="Permissions" link="{{ route('permissions.index') }}" :hidden="auth()->user()->cannot('view permissions')" />
                    <x-menu-item title="User Logs" link="{{ route('user-logs.index') }}" :hidden="auth()->user()->cannot('view user logs')" />
                </x-menu-sub>

                <x-menu-separator />
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- TOAST area --}}
    <x-toast position="toast-top toast-right" />

    {{-- Theme toggle --}}
    <x-theme-toggle class="hidden" />

    @livewireScriptConfig
</body>
</html>
