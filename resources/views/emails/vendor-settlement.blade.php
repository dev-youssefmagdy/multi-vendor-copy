<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Invoice {{ $settlement->invoice_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .wrap {
            max-width: 600px;
            margin: 32px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .header {
            background: #1e293b;
            padding: 32px 36px;
        }

        .header h1 {
            color: #fff;
            font-size: 22px;
            margin: 0 0 4px;
        }

        .header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }

        .body {
            padding: 32px 36px;
        }

        .notice {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #166534;
        }

        .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .value {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 16px;
        }

        table.breakdown {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 13px;
        }

        table.breakdown td {
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        table.breakdown td:last-child {
            text-align: end;
            font-weight: 600;
            color: #1e293b;
        }

        table.breakdown tr.total td {
            border-bottom: none;
            padding-top: 12px;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .footer {
            background: #f8fafc;
            padding: 20px 36px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="header">
            <h1>Settlement Invoice</h1>
            <p>{{ $settlement->invoice_number }} &nbsp;·&nbsp; {{ now()->format('M d, Y') }}</p>
        </div>
        <div class="body">

            <div class="notice">
                ✓ &nbsp;Your payment to the central platform has been confirmed.
            </div>

            <div class="label">Invoice Number</div>
            <div class="value">{{ $settlement->invoice_number }}</div>

            <div class="label">Order Reference</div>
            <div class="value">{{ $settlement->order_uuid }}</div>

            @if($settlement->transaction_id)
                <div class="label">Transaction ID</div>
                <div class="value" style="font-family:monospace;">{{ $settlement->transaction_id }}</div>
            @endif

            @if($settlement->gateway_code)
                <div class="label">Payment Gateway</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $settlement->gateway_code)) }}</div>
            @endif

            <table class="breakdown">
                <tr>
                    <td>Product Cost</td>
                    <td>${{ number_format((float) $settlement->product_cost, 2) }}</td>
                </tr>
                <tr>
                    <td>Shipping Cost</td>
                    <td>${{ number_format((float) $settlement->shipping_cost, 2) }}</td>
                </tr>
                @if((float) $settlement->gateway_fee > 0)
                    <tr>
                        <td>Gateway Fee</td>
                        <td>+${{ number_format((float) $settlement->gateway_fee, 2) }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>Total Paid</td>
                    <td>${{ number_format((float) $settlement->total, 2) }}</td>
                </tr>
            </table>

            <p style="font-size:13px;color:#64748b;margin:0;">
                Please keep this confirmation for your records.
                If you have any questions, contact our support team.
            </p>
        </div>
        <div class="footer">
            This is an automated message. Please do not reply to this email.
        </div>
    </div>
</body>

</html>