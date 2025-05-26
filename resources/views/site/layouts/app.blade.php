<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="description" content="{{ config('site.meta.description') }}" />
    <meta name="keywords" content="{{ config('site.meta.keyword') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/favicon/favicon.svg') }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-inter antialiased bg-base-200">

    {{ $slot }}

    {{-- TOAST area --}}
    <x-toast position="toast-top toast-right" />

    {{-- Theme toggle --}}
    <x-theme-toggle class="hidden" />

    @livewireScriptConfig
</body>
</html>
