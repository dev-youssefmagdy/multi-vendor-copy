<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via Razorpay</title>
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
        <p>Opening Razorpay checkout…</p>
    </div>

    {{-- Hidden form to POST payment data back on success --}}
    <form id="rzp-callback-form" method="POST" action="{{ route('tenant.payment.success', 'razorpay') }}">
        @csrf
        <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
        <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
        <input type="hidden" name="razorpay_signature" id="rzp_signature">
    </form>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const options = {
            key: '{{ $key }}',
            amount:      {{ (int) round($amount * 100) }},
            currency: '{{ $currency }}',
            name: '{{ $name }}',
            description: '{{ $description }}',
            order_id: '{{ $order_id }}',
            prefill: {
                name: '{{ $name }}',
                email: '{{ $email }}',
                contact: '{{ $phone }}',
            },
            theme: { color: '#1b1b1b' },
            handler: function (response) {
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                document.getElementById('rzp_order_id').value = response.razorpay_order_id;
                document.getElementById('rzp_signature').value = response.razorpay_signature;
                document.getElementById('rzp-callback-form').submit();
            },
            modal: {
                ondismiss: function () {
                    window.location.href = '{{ route("tenant.payment.cancel", "razorpay") }}';
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            alert('Payment failed: ' + response.error.description);
            window.location.href = '{{ route("tenant.payment.cancel", "razorpay") }}';
        });

        // Auto-open on page load
        window.onload = function () { rzp.open(); };
    </script>
</body>

</html>