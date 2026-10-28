<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $host }}</title>
    <style>
        body{margin:0;font-family:system-ui,sans-serif;background:#f6f6f7;color:#111;display:flex;min-height:100vh;align-items:center;justify-content:center}
        main{background:#fff;border-radius:12px;padding:28px;max-width:380px;width:calc(100% - 32px);text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.08)}
        a.btn{display:block;margin:10px 0;padding:12px;border-radius:8px;background:#111;color:#fff;text-decoration:none}
        a.alt{background:#fff;color:#111;border:1px solid #ccc}
        small{color:#666;display:block;margin-top:14px}
    </style>
</head>
<body>
<main>
    <h1 style="font-size:1.25rem">{{ $host }}</h1>
    <a class="btn" href="{{ $appUrl }}">Open in app</a>
    @if ($playUrl)<a class="btn alt" href="{{ $playUrl }}">Install on Google Play</a>@endif
    @if ($iosUrl)<a class="btn alt" href="{{ $iosUrl }}">Install on the App Store</a>@endif
    <a class="btn alt" href="{{ $webUrl }}">Continue in browser</a>
    @if ($storeCode !== '')<small>Already installed? Enter this store code in the app: <strong>{{ $storeCode }}</strong></small>@endif
</main>
</body>
</html>
