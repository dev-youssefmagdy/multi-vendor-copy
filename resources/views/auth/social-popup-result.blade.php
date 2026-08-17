<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $success ? 'Signing in…' : 'Sign in failed' }}</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f9fafb;
            color: #374151;
        }
        .msg { text-align: center; }
        .spinner {
            width: 36px; height: 36px;
            border: 3px solid #e5e7eb;
            border-top-color: #f97316;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { font-size: 14px; color: #6b7280; margin: 0; }
    </style>
</head>
<body>
    <div class="msg">
        <div class="spinner"></div>
        <p>{{ $success ? __('Completing sign in…') : __('Something went wrong. Closing…') }}</p>
    </div>
    <script>
        (function () {
            var payload = {!! $payload !!};
            var origin  = {{ json_encode(config('app.url')) }};

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, origin);
            }

            setTimeout(function () { window.close(); }, 300);
        })();
    </script>
</body>
</html>
