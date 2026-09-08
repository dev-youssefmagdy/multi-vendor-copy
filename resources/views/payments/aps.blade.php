<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via Amazon Payment Services</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .loader { text-align: center; color: #555; }
        .loader p { margin-top: 12px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="loader"><p>Redirecting to payment page…</p></div>
    <form id="aps-form" method="POST" action="{{ $action_url }}">
        @foreach ($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
    <script>window.onload = function () { document.getElementById('aps-form').submit(); };</script>
</body>
</html>
