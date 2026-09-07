<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

@php
    $favicon = app(\App\Repositories\AppSettingRepository::class)->getByKeys(['favicon'])->get('favicon')?->value;
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('Ecommet — Build Your Online Store in Minutes') }}</title>
    <meta name="description"
        content="{{ $description ?? __('Build your e-commerce store in minutes. Manage products, payments and sales with ease.') }}">
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @include('layouts.website.styles')
    @stack('styles')
</head>

<body class="font-sans text-gray-800 bg-white antialiased {{ app()->getLocale() === 'ar' ? 'rtl' : '' }}">

    <div id="pageLoader" aria-hidden="true">
        <div class="page-loader-spinner"></div>
    </div>

    @include('layouts.website.navbar')

    {{ $slot }}
    @include('layouts.website.footer')
    @include('layouts.website.scripts')
    @stack('scripts')
</body>

</html>