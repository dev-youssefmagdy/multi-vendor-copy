<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="dark">

<head>
    {{-- Logo fonts (text-logo builder) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Montserrat:wght@400;700&family=Oswald:wght@400;700&family=Playfair+Display:wght@400;700&family=Poppins:wght@400;700&family=Tajawal:wght@400;700&display=swap">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>{{ tenant('name') ? tenant('name') . ' Vendor Panel' : 'Vendor Panel' }}</title>
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
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

    @include('layouts.tenant.sidebar')
    @include('layouts.tenant.header')

    @auth('tenant')
        @livewire('tenant.onboarding.onboarding-tour')
    @endauth

    {{ $slot ?? '' }}
    @yield('content')
    @livewireScripts
    @stack('scripts')
</body>

</html>
