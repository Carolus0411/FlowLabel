<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=roboto-mono:400,600" rel="stylesheet" />

        <style>
        html, body { margin:0; padding:10px; }
        @page { margin: 1cm; padding:10px; }
        @media print {
            html, body { margin:0; padding:0; }
            .no-print, .no-print *{
                display: none !important;
            }
        }
        .table td { font-size:14px; }
        .table-print { width:100%; border-spacing: 0; border-collapse:collapse !important; }
        .table-print tr th { font-size:12px; padding:5px; font-weight:bold; border:1px solid #000000; vertical-align:middle !important; text-align:center; }
        .table-print tr td { font-size:12px; padding:4px 5px; border-right:1px solid #000000; border-left:1px solid #000000; }
        .table-print tr.border-top td { border-top:1px solid #000000; }
        .table-print tr.border-bottom td { border-bottom:1px solid #000000; }
        .border-top { border-top:1px solid #000000; }
        .border-bottom { border-bottom:1px solid #000000; }
        .border-right { border-right:1px solid #000000; }
        .border-left { border-left:1px solid #000000; }
        </style>

        @vite(['resources/css/print.css'])

        {{-- Scripts --}}
        @stack('head')
    </head>
    <body class="font-inter antialiased">
        {{ $slot }}
    </body>
</html>
