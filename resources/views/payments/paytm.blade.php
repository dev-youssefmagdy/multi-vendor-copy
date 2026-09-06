<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via Paytm</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .loader { text-align: center; color: #555; }
    </style>
</head>
<body>
    <div class="loader"><p>Redirecting to Paytm…</p></div>
    <form id="paytm-form" method="POST" action="{{ $txnUrl }}">
        @foreach ($paramList as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <input type="hidden" name="CHECKSUMHASH" value="{{ $checkSum }}">
    </form>
    <script>window.onload = function () { document.getElementById('paytm-form').submit(); };</script>
</body>
</html>
