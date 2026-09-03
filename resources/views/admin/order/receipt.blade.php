@php
    $financials = $order['financials'] ?? [];
    $items = $order['items'] ?? [];
    $address = $order['shipping_address'] ?? [];
    $createdAt = $order['created_at'] ?? null;

    $taxTotal = (float) (($financials['items_tax'] ?? 0) + ($financials['tax'] ?? 0));
    $discountTotal = (float) (($financials['items_discount'] ?? 0) + ($financials['discount'] ?? 0));

    $addressParts = array_filter([
        $address['full_name'] ?? $address['name'] ?? null,
        $address['address_line_1'] ?? $address['address'] ?? $address['street'] ?? null,
        $address['address_line_2'] ?? $address['street2'] ?? null,
        $address['city'] ?? null,
        $address['state'] ?? $address['region'] ?? null,
        $address['postal_code'] ?? $address['zip'] ?? null,
        $address['country'] ?? null,
    ]);
    $addressHtml = implode('<br>', array_map('e', $addressParts));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Receipt · {{ $order['uuid'] ?? 'Order' }}</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        /* ─── Reset ──────────────────────────────────────────────────── */
        *,
        ::before,
        ::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-scheme: light
        }

        /* ─── Tokens ─────────────────────────────────────────────────── */
        :root {
            --ink: #0f172a;
            --ink2: #1e293b;
            --muted: #64748b;
            --muted2: #94a3b8;
            --line: #e2e8f0;
            --surface: #f8fafc;
            --white: #ffffff;
            --accent: #0ea5e9;
            --accent-dk: #0284c7;
            --green: #16a34a;
            --green-bg: #dcfce7;
            --green-bd: #86efac;
            --green-tx: #166534;
            --amber: #d97706;
            --amber-bg: #fef3c7;
            --amber-bd: #fcd34d;
            --amber-tx: #92400e;
            --blue-bg: #e0f2fe;
            --blue-bd: #7dd3fc;
            --blue-tx: #075985;
            --violet-bg: #ede9fe;
            --violet-bd: #c4b5fd;
            --violet-tx: #5b21b6;
            --red-bg: #fee2e2;
            --red-bd: #fca5a5;
            --red-tx: #991b1b;
        }

        /* ─── Screen shell ───────────────────────────────────────────── */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
            background: #eef2f7;
            color: var(--ink);
            min-height: 100vh
        }

        /* ─── Toolbar (screen only) ──────────────────────────────────── */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--ink2);
            flex: 1
        }

        .toolbar-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 1px
        }

        .tbtn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 9px;
            border: 1.5px solid var(--line);
            background: var(--white);
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: background .14s, border-color .14s;
        }

        .tbtn:hover {
            background: var(--surface);
            border-color: #cbd5e1
        }

        .tbtn-primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent)
        }

        .tbtn-primary:hover {
            background: var(--accent-dk);
            border-color: var(--accent-dk)
        }

        .tbtn svg {
            flex-shrink: 0
        }

        /* ─── Page wrapper ───────────────────────────────────────────── */
        .page-wrap {
            display: flex;
            justify-content: center;
            padding: 32px 16px 60px
        }

        /* ─── A4 Sheet ───────────────────────────────────────────────── */
        .sheet {
            width: 210mm;
            background: var(--white);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .07), 0 20px 60px -12px rgba(0, 0, 0, .12);
            border-radius: 6px;
            overflow: hidden;
        }

        /* ─── Header band ────────────────────────────────────────────── */
        .rcp-header {
            background: var(--ink);
            color: #fff;
            padding: 28px 32px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: contain;
            background: #fff;
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, .15);
            flex-shrink: 0;
        }

        .brand-fallback {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--accent), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 800;
            color: #fff;
        }

        .brand-meta h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.01em;
            color: #fff;
            line-height: 1.2
        }

        .brand-meta p {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 3px;
            line-height: 1.5
        }

        .doc-label {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .doc-label .word {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -.03em;
            color: #fff;
            line-height: 1;
        }

        .doc-label .order-no {
            font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace;
            font-size: 13px;
            color: #94a3b8;
            letter-spacing: .04em;
        }

        .doc-label .date {
            font-size: 12px;
            color: #94a3b8
        }

        /* ─── Status pills row ───────────────────────────────────────── */
        .status-band {
            background: var(--ink2);
            padding: 10px 32px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            border: 1px solid;
        }

        .pill-paid {
            background: var(--green-bg);
            color: var(--green-tx);
            border-color: var(--green-bd)
        }

        .pill-unpaid {
            background: var(--amber-bg);
            color: var(--amber-tx);
            border-color: var(--amber-bd)
        }

        .pill-status {
            background: var(--blue-bg);
            color: var(--blue-tx);
            border-color: var(--blue-bd)
        }

        .pill-ship {
            background: var(--violet-bg);
            color: var(--violet-tx);
            border-color: var(--violet-bd)
        }

        .pill-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            opacity: .8
        }

        /* ─── Body ───────────────────────────────────────────────────── */
        .rcp-body {
            padding: 28px 32px 32px
        }

        /* ─── Two-column info grid ───────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px
        }

        .info-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 16px 18px;
            background: var(--surface);
        }

        .info-card h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .info-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px
        }

        .info-row {
            display: flex;
            gap: 6px;
            font-size: 12.5px;
            color: #334155;
            margin-top: 5px;
            line-height: 1.5;
        }

        .info-lbl {
            color: var(--muted);
            min-width: 64px;
            flex-shrink: 0;
            font-weight: 500
        }

        /* ─── Section heading ────────────────────────────────────────── */
        .sec-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sec-head h2 {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: .01em
        }

        .sec-head-line {
            flex: 1;
            height: 1px;
            background: var(--line)
        }

        /* ─── Items table ────────────────────────────────────────────── */
        .item-img-wrap {
            width: 44px;
            height: 44px;
            border-radius: 7px;
            border: 1px solid var(--line);
            overflow: hidden;
            flex-shrink: 0;
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted2);
        }

        .item-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px
        }

        .items-table thead tr {
            background: var(--ink)
        }

        .items-table thead th {
            padding: 10px 12px;
            text-align: left;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .items-table thead th.r {
            text-align: right
        }

        .items-table tbody td {
            padding: 13px 12px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        .items-table tbody td.r {
            text-align: right;
            font-variant-numeric: tabular-nums
        }

        .items-table tbody tr:nth-child(even) td {
            background: #fafbfc
        }

        .items-table tbody tr:last-child td {
            border-bottom: none
        }

        .item-name {
            font-weight: 600;
            color: var(--ink);
            line-height: 1.4
        }

        .item-variant {
            color: var(--muted2);
            font-size: 11.5px;
            margin-top: 2px
        }

        /* ─── Totals ─────────────────────────────────────────────────── */
        .totals-wrap {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end
        }

        .totals-box {
            width: 320px;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            font-size: 13px;
        }

        .tot-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 16px;
        }

        .tot-row+.tot-row {
            border-top: 1px solid var(--line)
        }

        .tot-row .lbl {
            color: var(--muted)
        }

        .tot-row .val {
            font-variant-numeric: tabular-nums;
            font-weight: 500
        }

        .tot-sep {
            border-top: 1px solid var(--line);
            background: var(--surface)
        }

        .tot-grand {
            background: var(--ink);
        }

        .tot-grand .lbl {
            color: #94a3b8;
            font-weight: 600;
            font-size: 14px
        }

        .tot-grand .val {
            color: #fff;
            font-weight: 800;
            font-size: 16px
        }

        /* ─── Footer ─────────────────────────────────────────────────── */
        .rcp-footer {
            margin-top: 28px;
            padding: 16px 20px;
            background: linear-gradient(135deg, #f0f9ff, #f0fdf4);
            border: 1px solid #bae6fd;
            border-radius: 10px;
            text-align: center;
        }

        .rcp-footer .thanks {
            font-size: 14px;
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 4px
        }

        .rcp-footer .contact {
            font-size: 12px;
            color: var(--muted)
        }

        .rcp-stamp {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--muted2);
        }

        /* ─── Print ──────────────────────────────────────────────────── */
        @media print {
            .toolbar {
                display: none !important
            }

            body {
                background: #fff
            }

            .page-wrap {
                padding: 0
            }

            .sheet {
                width: 210mm;
                min-height: 297mm;
                box-shadow: none;
                border-radius: 0;
            }

            @page {
                size: A4 portrait;
                margin: 0
            }
        }
    </style>
</head>

<body>

    {{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Order Receipt</div>
            <div class="toolbar-sub">#{{ $order['uuid'] ?? '' }} · {{ $tenant['name'] ?? 'Store' }}</div>
        </div>
        <a href="{{ url()->previous() }}" class="tbtn" style="margin-left:auto">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            Back
        </a>
        <button type="button" class="tbtn" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9" />
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" />
            </svg>
            Print A4
        </button>
        <button type="button" class="tbtn tbtn-primary" onclick="downloadPdf()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Download PDF
        </button>
    </div>

    {{-- ── Sheet ─────────────────────────────────────────────────────────── --}}
    <div class="page-wrap">
        <div class="sheet" id="rcp">

            {{-- Header --}}
            <div class="rcp-header">
                <div class="brand">
                    @if (!empty($tenant['logo']))
                        <img src="{{ $tenant['logo'] }}" alt="{{ $tenant['name'] }}" class="brand-logo">
                    @else
                        <div class="brand-fallback">{{ strtoupper(substr($tenant['name'] ?? 'S', 0, 1)) }}</div>
                    @endif
                    <div class="brand-meta">
                        <h1>{{ $tenant['name'] ?? 'Store' }}</h1>
                        @if (!empty($tenant['domain']))
                        <p>{{ $tenant['domain'] }}</p>@endif
                        @if (!empty($tenant['email']))
                        <p>{{ $tenant['email'] }}</p>@endif
                        @if (!empty($tenant['phone']))
                        <p>{{ $tenant['phone'] }}</p>@endif
                    </div>
                </div>
                <div class="doc-label">
                    <div class="word">RECEIPT</div>
                    <div class="order-no">#{{ $order['uuid'] ?? '-' }}</div>
                    <div class="date">{{ optional($createdAt)->format('M d, Y · H:i') ?? '-' }}</div>
                </div>
            </div>

            {{-- Status band --}}
            <div class="status-band">
                @if (!empty($order['paid']))
                    <span class="pill pill-paid"><span class="pill-dot"></span>Paid</span>
                @else
                    <span class="pill pill-unpaid"><span class="pill-dot"></span>Unpaid</span>
                @endif
                @if (!empty($order['status']))
                    <span class="pill pill-status"><span class="pill-dot"></span>{{ $order['status'] }}</span>
                @endif
                @if (!empty($order['shipping_status']))
                    <span class="pill pill-ship"><span class="pill-dot"></span>{{ $order['shipping_status'] }}</span>
                @endif
            </div>

            <div class="rcp-body">

                {{-- Info grid --}}
                <div class="info-grid">

                    {{-- Billed To --}}
                    <div class="info-card">
                        <h3>Billed To</h3>
                        <div class="info-name">{{ $order['customer']['name'] ?? 'Guest' }}</div>
                        @if (!empty($order['customer']['email']))
                            <div class="info-row"><span class="info-lbl">Email</span>{{ $order['customer']['email'] }}</div>
                        @endif
                        @if (!empty($order['customer']['phone']))
                            <div class="info-row"><span class="info-lbl">Phone</span>{{ $order['customer']['phone'] }}</div>
                        @endif
                        @if ($addressHtml)
                            <div class="info-row"><span class="info-lbl">Address</span><span>{!! $addressHtml !!}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Order Info --}}
                    <div class="info-card">
                        <h3>Order Information</h3>
                        <div class="info-name" style="font-family:ui-monospace,monospace;font-size:14px">
                            #{{ $order['uuid'] ?? '-' }}</div>
                        <div class="info-row"><span
                                class="info-lbl">Date</span>{{ optional($createdAt)->format('M d, Y · H:i') ?? '-' }}
                        </div>
                        @if (!empty($order['tracking_number']))
                            <div class="info-row"><span class="info-lbl">Tracking</span><strong
                                    style="font-family:ui-monospace,monospace">{{ $order['tracking_number'] }}</strong>
                            </div>
                        @endif
                        @if (!empty($order['payment_gateway']) || !empty($order['payment_method']))
                            <div class="info-row"><span
                                    class="info-lbl">Payment</span>{{ $order['payment_gateway'] ?? $order['payment_method'] }}
                            </div>
                        @endif
                        @if (!empty($order['payment_reference']))
                            <div class="info-row"><span class="info-lbl">Ref #</span><span
                                    style="font-family:ui-monospace,monospace;font-size:11.5px">{{ $order['payment_reference'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="sec-head">
                    <h2>Order Items</h2>
                    <div class="sec-head-line"></div>
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:44%">Product</th>
                            <th class="r" style="width:8%">Qty</th>
                            <th class="r" style="width:14%">Unit Price</th>
                            <th class="r" style="width:14%">Discount</th>
                            <th class="r" style="width:20%">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $qty = (int) ($item['qty'] ?? 0);
                                $price = (float) ($item['price'] ?? 0);
                                $disc = (float) ($item['discount'] ?? 0);
                                $lineTotal = (float) ($item['line_total'] ?? ($qty * $price - $disc));
                            @endphp
                            <tr>
                                <td>
                                    <div class="item-cell">
                                        <div class="item-img-wrap">
                                            @if (!empty($item['image_url']))
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] ?? '' }}"
                                                    loading="lazy">
                                            @else
                                                <div class="item-img-placeholder">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                                        <polyline points="21 15 16 10 5 21" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="item-name">{{ $item['name'] ?? '-' }}</div>
                                            @if (!empty($item['variant']))
                                                <div class="item-variant">{{ $item['variant'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="r">{{ $qty }}</td>
                                <td class="r">${{ number_format($price, 2) }}</td>
                                <td class="r">{{ $disc > 0 ? '−$' . number_format($disc, 2) : '—' }}</td>
                                <td class="r"><strong>${{ number_format($lineTotal, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">
                                    No items recorded for this order.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="totals-wrap">
                    <div class="totals-box">
                        <div class="tot-row">
                            <span class="lbl">Subtotal</span>
                            <span class="val">${{ number_format((float) ($financials['subtotal'] ?? 0), 2) }}</span>
                        </div>
                        @if ($discountTotal > 0)
                            <div class="tot-row">
                                <span class="lbl">Discount</span>
                                <span class="val" style="color:var(--green)">−${{ number_format($discountTotal, 2) }}</span>
                            </div>
                        @endif
                        <div class="tot-row">
                            <span class="lbl">Shipping</span>
                            <span
                                class="val">${{ number_format((float) ($financials['shipping_total'] ?? 0), 2) }}</span>
                        </div>
                        @if ($taxTotal > 0)
                            <div class="tot-row">
                                <span class="lbl">Tax</span>
                                <span class="val">${{ number_format($taxTotal, 2) }}</span>
                            </div>
                        @endif
                        <div class="tot-row tot-grand">
                            <span class="lbl">Grand Total</span>
                            <span class="val">${{ number_format((float) ($financials['grand_total'] ?? 0), 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Thank-you --}}
                <div class="rcp-footer">
                    <div class="thanks">Thank you for your purchase!</div>
                    <div class="contact">
                        Questions? Contact
                        <strong>{{ $tenant['email'] ?? ($tenant['name'] ?? 'the store') }}</strong>
                    </div>
                </div>

                <div class="rcp-stamp">
                    <span>Generated {{ now()->format('M d, Y H:i') }}</span>
                    <span>Order #{{ $order['uuid'] ?? '-' }} · {{ $tenant['name'] ?? '' }}</span>
                </div>
            </div>{{-- /rcp-body --}}

        </div>{{-- /sheet --}}
    </div>{{-- /page-wrap --}}

    <script>
        function downloadPdf() {
            var el = document.getElementById('rcp');
            var fname = 'receipt-{{ addslashes($order['uuid'] ?? 'order') }}.pdf';
            if (typeof html2pdf === 'undefined') { window.print(); return; }
            html2pdf().set({
                margin: 0,
                filename: fname,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            }).from(el).save();
        }
    </script>
</body>

</html>