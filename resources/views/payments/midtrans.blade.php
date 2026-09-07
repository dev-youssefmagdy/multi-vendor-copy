<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via Midtrans</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }

        .loader {
            text-align: center;
            color: #555;
        }

        .loader p {
            margin-top: 12px;
            font-size: 14px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #ddd;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>Opening Midtrans payment…</p>
    </div>

    @php
        $snapUrl = $is_production
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp

    <script src="{{ $snapUrl }}" data-client-key="{{ $client_key }}">
    </script>
    <script>
        window.onload = function () {
            snap.pay('{{ $snap_token }}', {
                onSuccess: function (result) {
                    window.location.href = '{{ $success_url }}' + '?transaction_id=' + result.transaction_id + '&order_id=' + result.order_id;
                },
                onPending: function (result) {
                    window.location.href = '{{ $success_url }}' + '?transaction_id=' + result.transaction_id + '&order_id=' + result.order_id;
                },
                onError: function (result) {
                    alert('Payment failed: ' + JSON.stringify(result));
                    window.location.href = '{{ $cancel_url }}';
                },
                onClose: function () {
                    window.location.href = '{{ $cancel_url }}';
                }
            });
        };
    </script>
</body>

</html>