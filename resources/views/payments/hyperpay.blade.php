<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay via HyperPay</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .loader { text-align: center; color: #555; }
        .loader p { margin-top: 12px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="loader"><p>Loading HyperPay checkout…</p></div>
    <form action="{{ route('tenant.payment.success', 'hyperpay') }}" class="paymentWidgets"
          data-brands="VISA MASTER AMEX"></form>
    @php
        $wpwlUrl = $sandbox
            ? "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId={$checkout_id}"
            : "https://eu-prod.oppwa.com/v1/paymentWidgets.js?checkoutId={$checkout_id}";
    @endphp
    <script src="{{ $wpwlUrl }}"></script>
</body>
</html>
