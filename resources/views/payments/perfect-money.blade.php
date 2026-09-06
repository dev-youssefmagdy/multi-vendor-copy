<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via Perfect Money</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .loader { text-align: center; color: #555; }
    </style>
</head>
<body>
    <div class="loader"><p>Redirecting to Perfect Money…</p></div>
    <form id="pm-form" method="POST" action="{{ $postUrl }}">
        @foreach ($formData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
    <script>window.onload = function () { document.getElementById('pm-form').submit(); };</script>
</body>
</html>
