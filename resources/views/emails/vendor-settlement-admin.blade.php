<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Vendor Settlement – {{ $settlement->invoice_number }}</title>
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
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #1e40af;
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

        .cta {
            text-align: center;
            margin: 28px 0 8px;
        }

        .cta a {
            display: inline-block;
            background: #1e293b;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 6px;
            letter-spacing: .3px;
        }

        .cta a:hover {
            background: #334155;
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
            <h1>New Vendor Settlement</h1>
            <p>{{ $settlement->invoice_number }} &nbsp;·&nbsp;
                {{ $settlement->settled_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i') }}</p>
        </div>

        <div class="body">

            <div class="notice">
                &#128176; &nbsp;A vendor has completed a settlement payment to the platform.
            </div>

            <div class="label">Invoice Number</div>
            <div class="value">{{ $settlement->invoice_number }}</div>

            <div class="label">Vendor / Tenant</div>
            <div class="value">
                {{ $settlement->tenant_name ?? $settlement->tenant_id }}
                @if ($settlement->tenant_email)
                    <br><span style="font-size:12px;color:#64748b;">{{ $settlement->tenant_email }}</span>
                @endif
            </div>

            <div class="label">Order Reference</div>
            <div class="value" style="font-family:monospace;font-size:13px;">{{ $settlement->order_uuid }}</div>

            @if ($settlement->transaction_id)
                <div class="label">Transaction ID</div>
                <div class="value" style="font-family:monospace;font-size:13px;">{{ $settlement->transaction_id }}</div>
            @endif

            @if ($settlement->gateway_code)
                <div class="label">Payment Gateway</div>
                <div class="value">{{ ucwords(str_replace('_', ' ', $settlement->gateway_code)) }}</div>
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
                @if ((float) $settlement->gateway_fee > 0)
                    <tr>
                        <td>Gateway Fee</td>
                        <td>+${{ number_format((float) $settlement->gateway_fee, 2) }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>Total Received</td>
                    <td>${{ number_format((float) $settlement->total, 2) }}</td>
                </tr>
            </table>

            <div class="cta">
                <a href="{{ $settlementsUrl }}">View Settlement Details</a>
            </div>

        </div>

        <div class="footer">
            This is an automated notification from your platform. Please do not reply to this email.
        </div>
    </div>
</body>

</html>