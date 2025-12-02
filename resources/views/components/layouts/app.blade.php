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

    {{-- MonthSelectPlugin  --}}
    <script src="https://unpkg.com/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <link href="https://unpkg.com/flatpickr/dist/plugins/monthSelect/style.css" rel="stylesheet">

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

    {{-- EasyMDE --}}
    {{-- <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css"> --}}
    {{-- <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script> --}}

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @filepondScripts
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-inter antialiased bg-base-200 dark:bg-base-200">

    {{-- <div class="fixed top-0 z-[999] w-full flex justify-center">
        <div wire:loading class="py-0.5 px-4 text-xs flex items-center gap-3 bg-red-300">
            <span class="loading loading-dots loading-md"></span>
            Loading ...
        </div>
    </div> --}}

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
                <x-menu-item title="Request" icon="o-inbox-arrow-down" link="{{ route('request.index') }}" />

                    <x-menu-sub title="Sales" icon="o-shopping-cart">
                    <x-menu-item title="Sales Invoice" link="{{ route('sales-invoice.index') }}" :hidden="auth()->user()->cannot('view sales-invoice')" />
                    <x-menu-item title="Sales Settlement" link="{{ route('sales-settlement.index') }}" :hidden="auth()->user()->cannot('view sales-settlement')" />
                        <x-menu-item title="AR Outstanding" link="{{ route('sales.ar-outstanding') }}" :hidden="auth()->user()->cannot('view sales-invoice')" />
                </x-menu-sub>

                    <x-menu-sub title="Purchase" icon="o-shopping-cart">
                        <x-menu-item title="Purchase Invoice" link="{{ route('purchase-invoice.index') }}" :hidden="auth()->user()->cannot('view purchase-invoice')" />
                        <x-menu-item title="Purchase Settlement" link="{{ route('purchase-settlement.index') }}" :hidden="auth()->user()->cannot('view purchase-settlement')" />
                        <x-menu-item title="AP Outstanding" link="{{ route('purchase.ap-outstanding') }}" :hidden="auth()->user()->cannot('view purchase-invoice')" />
                    </x-menu-sub>

                <x-menu-sub title="Cash And Bank" icon="o-banknotes">
                    <x-menu-item title="Cash In" link="{{ route('cash-in.index') }}" :hidden="auth()->user()->cannot('view cash-in')" />
                    <x-menu-item title="Cash Out" link="{{ route('cash-out.index') }}" :hidden="auth()->user()->cannot('view cash-out')" />
                    <x-menu-item title="Bank In" link="{{ route('bank-in.index') }}" :hidden="auth()->user()->cannot('view bank-in')" />
                    <x-menu-item title="Bank Out" link="{{ route('bank-out.index') }}" :hidden="auth()->user()->cannot('view bank-out')" />
                    <x-menu-item title="Prepaid Account" link="{{ route('prepaid-account.index') }}" :hidden="auth()->user()->cannot('view prepaid-account')" />
                </x-menu-sub>

                <x-menu-sub title="General Ledger" icon="o-clipboard-document-list">
                    <x-menu-item title="Journal" link="{{ route('journal.index') }}" :hidden="auth()->user()->cannot('view journal')" />
                    <x-menu-item title="Opening Balance" link="{{ route('opening-balance.index') }}" :hidden="auth()->user()->cannot('view opening-balance')" />
                </x-menu-sub>

                <x-menu-sub title="Report" icon="o-chart-pie">
                    <x-menu-sub title="Financial">
                        <x-menu-item title="General Ledger" link="{{ route('report.general-ledger') }}" :hidden="auth()->user()->cannot('view general-ledger-report')" />
                        <x-menu-item title="Trial Balance" link="{{ route('report.trial-balance') }}" :hidden="auth()->user()->cannot('view trial-balance')" />
                        <x-menu-item title="Balance Sheet" link="{{ route('report.balance-sheet') }}" :hidden="auth()->user()->cannot('view balance-sheet')" />
                        <x-menu-item title="Profit Loss" link="{{ route('report.profit-loss') }}" :hidden="auth()->user()->cannot('view profit-loss')" />
                    </x-menu-sub>
                </x-menu-sub>

                <x-menu-sub title="Master" icon="o-circle-stack">
                    <x-menu-item title="Contact" link="{{ route('contact.index') }}" :hidden="auth()->user()->cannot('view contact')" />
                    <x-menu-item title="Supplier" link="{{ route('supplier.index') }}" :hidden="auth()->user()->cannot('view supplier')" />
                    <x-menu-item title="PPN" link="{{ route('ppn.index') }}" :hidden="auth()->user()->cannot('view ppn')" />
                    <x-menu-item title="PPH" link="{{ route('pph.index') }}" :hidden="auth()->user()->cannot('view pph')" />
                    <x-menu-item title="Chart Of Account" link="{{ route('coa.index') }}" :hidden="auth()->user()->cannot('view coa')" />
                    <x-menu-item title="Service Charge" link="{{ route('service-charge.index') }}" :hidden="auth()->user()->cannot('view service charge')" />
                    <x-menu-item title="Currency" link="{{ route('currency.index') }}" :hidden="auth()->user()->cannot('view currency')" />
                    <x-menu-item title="Uom" link="{{ route('uom.index') }}" :hidden="auth()->user()->cannot('view uom')" />
                    <x-menu-sub title="Cash And Bank">
                        <x-menu-item title="Bank" link="{{ route('bank.index') }}" :hidden="auth()->user()->cannot('view bank')" />
                        <x-menu-item title="Bank Account" link="{{ route('bank-account.index') }}" :hidden="auth()->user()->cannot('view bank-account')" />
                        <x-menu-item title="Cash Account" link="{{ route('cash-account.index') }}" :hidden="auth()->user()->cannot('view cash-account')" />
                    </x-menu-sub>
                </x-menu-sub>

                <x-menu-sub title="Setup" icon="o-cog-6-tooth">
                    <x-menu-item title="Settings" link="{{ route('setting.general') }}" :hidden="auth()->user()->cannot('view general-setting')" />
                    <x-menu-item title="Account Mapping" link="{{ route('setting.account-mapping') }}" :hidden="auth()->user()->cannot('view account-mapping')" />
                    <x-menu-item title="Code" link="{{ route('setting.code') }}" :hidden="auth()->user()->cannot('view setting-code')" />
                    <x-menu-item title="Draft" link="{{ route('setting.draft') }}" :hidden="auth()->user()->cannot('view draft')" />
                    <x-menu-item title="Send Test Mail" link="{{ route('mail.test') }}" :hidden="auth()->user()->cannot('send test-mail')" />
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
