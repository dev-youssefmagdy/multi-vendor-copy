<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
<title>{{ $panelTitle ?? 'Panel Login' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="auth-shell">
<div class="auth-stage">
  <div class="auth-backdrop auth-backdrop-a"></div>
  <div class="auth-backdrop auth-backdrop-b"></div>
  {{ $slot }}
</div>
@livewireScripts
</body>
</html>
