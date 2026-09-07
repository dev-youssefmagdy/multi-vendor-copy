<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>Payment Received – {{ $payout->invoice_number }}</title>
    <style>
        body {
            font-family: -apple-system, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .wrap {
            max-width: 620px;
            margin: 32px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .header {
            background: #0f766e;
            padding: 32px 36px;
        }

        .header h1 {
            color: #fff;
            font-size: 22px;
            margin: 0 0 4px;
        }

        .header p {
            color: #a7f3d0;
            font-size: 13px;
            margin: 0;
        }

        .body {
            padding: 32px 36px;
        }

        .notice {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #065f46;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .row:last-child {
            border-bottom: none;
        }

        .row .l {
            color: #64748b;
        }

        .row .v {
            color: #0f172a;
            font-weight: 600;
        }

        .total {
            margin-top: 16px;
            padding: 14px 18px;
            background: #f1f5f9;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 700;
        }

        .footer {
            background: #f8fafc;
            padding: 18px 36px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }

        .note {
            font-size: 12px;
            color: #475569;
            background: #fffbeb;
            border-inline-start: 3px solid #f59e0b;
            padding: 10px 14px;
            margin-top: 18px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="header">
            <h1>Payment received from the platform</h1>
            <p>Invoice {{ $payout->invoice_number }}</p>
        </div>
        <div class="body">
            <div class="notice">
                Hi {{ $payout->tenant_name ?? 'there' }}, the central platform has issued a payout to your store.
                Details below.
            </div>

            <div class="row"><span class="l">Invoice number</span><span class="v">{{ $payout->invoice_number }}</span>
            </div>
            <div class="row"><span class="l">Method</span><span class="v">
                    {{ $payout->method === 'bank' ? ('Bank transfer · ' . ($payout->bank_name ?? '—')) : ('Payment gateway · ' . ($payout->gateway_name ?? '—')) }}
                </span></div>
            @if ($payout->bank_account)
                <div class="row"><span class="l">Bank account / IBAN</span><span
                        class="v">{{ $payout->bank_account }}</span></div>
            @endif
            @if ($payout->transaction_reference)
                <div class="row"><span class="l">Transaction reference</span><span class="v"
                        style="font-family:monospace;">{{ $payout->transaction_reference }}</span></div>
            @endif
            <div class="row"><span class="l">Paid at</span><span
                    class="v">{{ $payout->paid_at?->format('M d, Y · H:i') }}</span></div>

            <div class="total">
                <span>Amount paid</span>
                <span>{{ $payout->currency ?? 'USD' }} {{ number_format((float) $payout->amount, 2) }}</span>
            </div>

            @if ($payout->note)
                <div class="note">{!! nl2br(e($payout->note)) !!}</div>
            @endif
        </div>
        <div class="footer">This is an automated notification. If anything looks off, contact platform support.</div>
    </div>
</body>

</html>