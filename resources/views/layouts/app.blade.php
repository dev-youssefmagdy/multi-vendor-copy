<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Nexus Admin - Dashboard</title>
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>

<body>

    @if (session('status'))
        <div id="flash-status" data-message="{{ session('status') }}" data-type="{{ session('status_type', 'success') }}"
            hidden></div>
    @endif

    <div id="ov" data-action="close-mobile"></div>

    @include('layouts.app.sidebar')
    @include('layouts.app.header')

    {{ $slot ?? '' }}
    @yield('content')
    @livewireScripts
    @stack('scripts')
</body>

</html>