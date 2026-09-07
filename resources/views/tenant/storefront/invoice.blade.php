<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Invoice') }} — {{ $payload['invoice_number'] }}</title>

    {{-- Google Fonts: Noto Sans for Latin, Noto Sans Arabic for Arabic --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Montserrat:wght@400;700&family=Oswald:wght@400;700&family=Playfair+Display:wght@400;700&family=Poppins:wght@400;700&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #1d4ed8;
            --primary-light: #eff6ff;
            --accent:    #0ea5e9;
            --text:      #111827;
            --muted:     #6b7280;
            --border:    #e5e7eb;
            --bg:        #f9fafb;
            --card:      #ffffff;
            --success:   #16a34a;
            --success-bg:#dcfce7;
            --radius:    12px;
            --radius-sm: 8px;
            --shadow:    0 4px 24px 0 rgba(17,24,39,.08), 0 1px 4px 0 rgba(17,24,39,.04);
        }

        html[dir="rtl"] body {
            font-family: 'Noto Sans Arabic', 'Noto Sans', sans-serif;
        }
        html[dir="ltr"] body {
            font-family: 'Noto Sans', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-size: 15px;
            line-height: 1.65;
            min-height: 100vh;
            padding: 40px 16px 80px;
        }

        /* ── Page wrapper ── */
        .invoice-wrap {
            max-width: 820px;
            margin: 0 auto;
        }

        /* ── Card ── */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* ── Header band ── */
        .inv-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 60%, #0ea5e9 100%);
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .inv-header .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .inv-header .logo-wrap {
            width: 72px;
            height: 72px;
            border-radius: var(--radius-sm);
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .inv-header .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .inv-header .logo-text {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.1;
            text-align: center;
            padding: 4px;
            word-break: break-word;
        }

        .inv-header .logo-initials {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }

        .inv-header .store-name {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .inv-header .inv-meta {
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        .inv-header .inv-number {
            color: rgba(255,255,255,.7);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 4px;
        }

        .inv-header .inv-id {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
        }

        .inv-header .inv-date {
            color: rgba(255,255,255,.75);
            font-size: 13px;
            margin-top: 4px;
        }

        /* ── Status pill ── */
        .status-bar {
            background: var(--success-bg);
            border-bottom: 1px solid #bbf7d0;
            padding: 10px 40px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            flex-shrink: 0;
        }

        .status-bar span {
            color: var(--success);
            font-size: 13px;
            font-weight: 600;
        }

        /* ── Body ── */
        .inv-body {
            padding: 36px 40px;
        }

        /* ── Info grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .inv-header { padding: 24px 20px; }
            .inv-body { padding: 24px 20px; }
            .status-bar { padding: 10px 20px; }
        }

        .info-block {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
        }

        .info-block-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--primary);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            padding: 4px 0;
            font-size: 14px;
        }

        .info-row .label {
            color: var(--muted);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .info-row .value {
            color: var(--text);
            font-weight: 500;
            text-align: {{ $isRtl ? 'left' : 'right' }};
            word-break: break-all;
        }

        /* ── Section heading ── */
        .section-heading {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            margin: 28px 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-heading::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            font-size: 14px;
        }

        .items-table thead {
            background: #f1f5f9;
        }

        .items-table th {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .items-table th:first-child { text-align: {{ $isRtl ? 'right' : 'left' }}; }
        .items-table th:not(:first-child) { text-align: center; }
        .items-table th:last-child { text-align: {{ $isRtl ? 'left' : 'right' }}; }

        .items-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td { border-bottom: none; }

        .items-table tbody tr:hover { background: var(--primary-light); }

        .item-name {
            font-weight: 600;
            color: var(--text);
        }

        .item-variant {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .td-center { text-align: center; }
        .td-right  { text-align: {{ $isRtl ? 'left' : 'right' }}; }
        .td-left   { text-align: {{ $isRtl ? 'right' : 'left' }}; }

        /* ── Totals ── */
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .totals-table {
            min-width: 280px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 18px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
        }

        .totals-row:last-child { border-bottom: none; }

        .totals-row .t-label { color: var(--muted); }
        .totals-row .t-value { font-weight: 600; }

        .totals-row.grand {
            background: var(--primary);
            color: #fff;
        }

        .totals-row.grand .t-label,
        .totals-row.grand .t-value {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        /* ── Footer ── */
        .inv-footer {
            border-top: 1px solid var(--border);
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            background: var(--bg);
        }

        .inv-footer strong { color: var(--text); }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 20px;
            transition: opacity .15s;
        }

        .back-link:hover { opacity: .75; }

        .back-arrow {
            display: inline-block;
            font-style: normal;
            font-size: 18px;
            line-height: 1;
        }

        /* ── Top action bar ── */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn-download:hover { opacity: .85; }

        /* ── Print / PDF styles ── */
        @media print {
            @page { margin: 18mm 14mm; size: A4; }

            body {
                background: #fff !important;
                padding: 0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* hide non-printable chrome */
            .action-bar,
            .back-link { display: none !important; }

            .invoice-wrap { max-width: 100% !important; }

            .card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }

            /* header: strip gradient → solid dark bar */
            .inv-header {
                background: #1a1a1a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* force all coloured text to black/dark */
            .info-block-title { color: #000 !important; }
            .back-link { display: none !important; }
            .status-bar {
                background: #f0f0f0 !important;
                border-color: #ccc !important;
            }
            .status-dot { background: #555 !important; }
            .status-bar span { color: #000 !important; }

            .totals-row.grand {
                background: #1a1a1a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .totals-row.grand .t-label,
            .totals-row.grand .t-value { color: #fff !important; }

            /* discount red → dark */
            .t-value[style*="color:#dc2626"] { color: #000 !important; }

            .items-table tbody tr:hover { background: none !important; }

            .inv-footer { background: #f5f5f5 !important; }
        }
    </style>
</head>
<body>
<div class="invoice-wrap">

    {{-- Action bar --}}
    <div class="action-bar">
        <a href="{{ url()->previous() }}" class="back-link">
            <span class="back-arrow">{{ $isRtl ? '→' : '←' }}</span>
            {{ __('Back') }}
        </a>
        <button class="btn-download" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            {{ __('Download PDF') }}
        </button>
    </div>

    <div class="card">

        {{-- ── Header ── --}}
        <div class="inv-header">
            <div class="brand">
                <div class="logo-wrap" @if($logo['mode'] === 'text') style="background-color:{{ $logo['bg_color'] }};border-radius:{{ $logo['shape'] === 'rounded' ? '999px' : 'var(--radius-sm)' }}" @endif>
                    @if($logo['mode'] === 'image')
                        <img src="{{ $logo['image_url'] }}" alt="{{ $storeName }}">
                    @else
                        <span class="logo-text"
                            style="font-family:{{ $logo['font_family'] }};color:{{ $logo['color'] }}">{{ $logo['text'] }}</span>
                    @endif
                </div>
                <div class="store-name">{{ $storeName }}</div>
            </div>

            <div class="inv-meta">
                <div class="inv-number">{{ __('Invoice') }}</div>
                <div class="inv-id">{{ $payload['invoice_number'] }}</div>
                <div class="inv-date">{{ $payload['issued_at'] }}</div>
            </div>
        </div>

        {{-- ── Paid status bar ── --}}
        @if($order->paid)
        <div class="status-bar">
            <div class="status-dot"></div>
            <span>{{ __('Payment Confirmed') }}</span>
        </div>
        @endif

        <div class="inv-body">

            {{-- ── Info grid ── --}}
            <div class="info-grid">

                {{-- Customer info --}}
                <div class="info-block">
                    <div class="info-block-title">{{ __('Customer') }}</div>
                    <div class="info-row">
                        <span class="label">{{ __('Name') }}</span>
                        <span class="value">{{ $payload['customer_name'] }}</span>
                    </div>
                    @if($payload['customer_email'])
                    <div class="info-row">
                        <span class="label">{{ __('Email') }}</span>
                        <span class="value">{{ $payload['customer_email'] }}</span>
                    </div>
                    @endif
                    @if(count($payload['shipping_lines']) > 0)
                    <div class="info-row" style="align-items:flex-start;">
                        <span class="label">{{ __('Address') }}</span>
                        <span class="value">{{ implode(', ', $payload['shipping_lines']) }}</span>
                    </div>
                    @endif
                </div>

                {{-- Payment info --}}
                <div class="info-block">
                    <div class="info-block-title">{{ __('Payment Details') }}</div>
                    <div class="info-row">
                        <span class="label">{{ __('Order #') }}</span>
                        <span class="value" style="font-size:12px;">{{ $payload['order_number'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">{{ __('Method') }}</span>
                        <span class="value">{{ $payload['payment_method'] }}</span>
                    </div>
                    @if($payload['payment_reference'] && $payload['payment_reference'] !== '-')
                    <div class="info-row">
                        <span class="label">{{ __('Reference') }}</span>
                        <span class="value" style="font-size:12px;">{{ $payload['payment_reference'] }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="label">{{ __('Currency') }}</span>
                        <span class="value">{{ $payload['currency'] }}</span>
                    </div>
                </div>
            </div>

            {{-- ── Line items ── --}}
            <div class="section-heading">{{ __('Order Items') }}</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th class="td-left">{{ __('Product') }}</th>
                        <th class="td-center">{{ __('Qty') }}</th>
                        <th class="td-center">{{ __('Unit Price') }}</th>
                        <th class="td-right">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['items'] as $item)
                    <tr>
                        <td class="td-left">
                            <div class="item-name">{{ $item['name'] }}</div>
                            @if(!empty($item['variant']))
                                <div class="item-variant">{{ $item['variant'] }}</div>
                            @endif
                        </td>
                        <td class="td-center">{{ $item['qty'] }}</td>
                        <td class="td-center">{{ $item['price'] }}</td>
                        <td class="td-right">{{ $item['total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ── Totals ── --}}
            <div class="totals-wrap">
                <div class="totals-table">
                    <div class="totals-row">
                        <span class="t-label">{{ __('Subtotal') }}</span>
                        <span class="t-value">{{ $payload['subtotal'] }}</span>
                    </div>
                    @if($payload['discount'] && !str_ends_with(trim($payload['discount']), '0.00'))
                    <div class="totals-row">
                        <span class="t-label">{{ __('Discount') }}</span>
                        <span class="t-value" style="color:#dc2626;">- {{ $payload['discount'] }}</span>
                    </div>
                    @endif
                    @if($payload['tax'] && !str_ends_with(trim($payload['tax']), '0.00'))
                    <div class="totals-row">
                        <span class="t-label">{{ __('Tax') }}</span>
                        <span class="t-value">{{ $payload['tax'] }}</span>
                    </div>
                    @endif
                    @if($payload['shipping'] && !str_ends_with(trim($payload['shipping']), '0.00'))
                    <div class="totals-row">
                        <span class="t-label">{{ __('Shipping') }}</span>
                        <span class="t-value">{{ $payload['shipping'] }}</span>
                    </div>
                    @endif
                    <div class="totals-row grand">
                        <span class="t-label">{{ __('Grand Total') }}</span>
                        <span class="t-value">{{ $payload['grand_total'] }}</span>
                    </div>
                </div>
            </div>

        </div>{{-- /inv-body --}}

        {{-- ── Footer ── --}}
        <div class="inv-footer">
            {{ __('Thank you for your order!') }}
            &nbsp;·&nbsp;
            <strong>{{ $storeName }}</strong>
        </div>

    </div>{{-- /card --}}
</div>
</body>
</html>
