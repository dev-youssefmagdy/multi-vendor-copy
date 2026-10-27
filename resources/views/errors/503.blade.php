@php
    $ctx = \App\Support\MaintenancePageContext::resolve(request());
    $retryAfter = (int) ($exception?->getHeaders()['Retry-After'] ?? 0);
    $labels = [
        'admin' => 'Admin panel', 'vendor' => 'Vendor panel', 'owner' => 'Owner panel',
    ];
    $label = $labels[$ctx['key']] ?? null;
    $appName = config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Under maintenance') }} - {{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: {{ $ctx['accent'] }}; --soft: {{ $ctx['soft'] }}; --dark: {{ $ctx['dark'] }}; }
        * { box-sizing: border-box; margin: 0; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            font-family: {!! $ctx['font'] !!}; color: var(--dark);
            background: @if($ctx['is_panel']) var(--dark) @else linear-gradient(135deg, var(--soft), #fff) @endif;
        }
        .card {
            width: 100%; max-width: 520px; text-align: center; background: #fff; border-radius: 20px;
            padding: 48px 32px; box-shadow: 0 20px 50px rgba(0, 0, 0, .12); border-top: 5px solid var(--accent);
        }
        .icon {
            width: 84px; height: 84px; border-radius: 50%; background: var(--soft); color: var(--accent);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;
        }
        .icon svg { width: 40px; height: 40px; animation: spin 6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .badge {
            display: inline-block; font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
            color: var(--accent); background: var(--soft); padding: 4px 12px; border-radius: 999px; margin-bottom: 16px;
        }
        h1 { font-size: 30px; font-weight: 800; margin-bottom: 12px; }
        p { color: #64748b; line-height: 1.6; font-size: 16px; }
        .meta { margin-top: 28px; font-size: 13px; color: #94a3b8; }
        .btn {
            margin-top: 28px; display: inline-block; background: var(--accent); color: #fff; border: 0; cursor: pointer;
            font: inherit; font-weight: 600; padding: 12px 28px; border-radius: 12px; text-decoration: none;
        }
        .btn:hover { opacity: .9; }
        @media (prefers-reduced-motion: reduce) { .icon svg { animation: none; } }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <span class="badge">{{ $label ? __($label) : $appName }} &middot; 503</span>
        <h1>{{ __('We\'ll be right back') }}</h1>
        <p>
            @if($ctx['is_panel'])
                {{ __('The panel is temporarily down for maintenance. Your data is safe; please try again in a few minutes.') }}
            @else
                {{ __('We\'re making some improvements and will be back shortly. Thank you for your patience.') }}
            @endif
        </p>
        @if($retryAfter > 0)
            <p class="meta">{{ __('Please try again in about :minutes minute(s).', ['minutes' => max(1, (int) ceil($retryAfter / 60))]) }}</p>
        @endif
        <button type="button" class="btn" onclick="location.reload()">{{ __('Try again') }}</button>
    </main>
    @if($retryAfter > 0)
        <script>setTimeout(function () { location.reload(); }, {{ min(max($retryAfter, 30), 300) * 1000 }});</script>
    @endif
</body>
</html>
