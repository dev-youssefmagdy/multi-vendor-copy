@php
    $address = $order['shipping_address'] ?? [];
    $addressParts = array_filter([
        $address['full_name'] ?? $address['name'] ?? null,
        $address['address_line_1'] ?? $address['address'] ?? $address['street'] ?? null,
        $address['address_line_2'] ?? $address['street2'] ?? null,
        $address['city'] ?? null,
        $address['state'] ?? $address['region'] ?? null,
        $address['postal_code'] ?? $address['zip'] ?? null,
        $address['country'] ?? null,
    ]);

    $customerName  = $address['full_name'] ?? $address['name'] ?? ($order['customer']['name'] ?? 'N/A');
    $customerPhone = $address['phone'] ?? ($order['customer']['phone'] ?? '');
    $orderNumber   = $order['uuid'] ?? $order['order_number'] ?? '-';
    $tracking      = $order['tracking_number'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Shipping Label · {{ $orderNumber }}</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        *,::before,::after{box-sizing:border-box;margin:0;padding:0}
        html{-webkit-print-color-adjust:exact;print-color-adjust:exact;color-scheme:light}
        body{font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;background:#eef2f7;color:#0f172a;min-height:100vh}

        /* Toolbar */
        .toolbar{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid #e2e8f0;padding:12px 24px;display:flex;align-items:center;gap:10px}
        .toolbar-title{font-weight:600;font-size:15px;color:#1e293b;flex:1}
        .toolbar-sub{font-size:12px;color:#64748b;margin-top:1px}
        .tbtn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:9px;border:1.5px solid #e2e8f0;background:#fff;color:#0f172a;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;transition:background .14s,border-color .14s}
        .tbtn:hover{background:#f8fafc;border-color:#cbd5e1}
        .tbtn-primary{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
        .tbtn-primary:hover{background:#0284c7;border-color:#0284c7}

        /* Page */
        .page-wrap{display:flex;justify-content:center;padding:32px 16px 60px;gap:24px;flex-wrap:wrap}

        /* Label card — 100×150mm */
        .label{width:100mm;background:#fff;box-shadow:0 4px 6px -1px rgba(0,0,0,.07),0 20px 60px -12px rgba(0,0,0,.12);border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;page-break-inside:avoid}

        /* Header band */
        .lbl-header{background:#0f172a;padding:14px 16px;display:flex;align-items:center;gap:12px}
        .lbl-logo{width:44px;height:44px;border-radius:7px;object-fit:contain;background:#fff;padding:4px;border:1px solid rgba(255,255,255,.15);flex-shrink:0}
        .lbl-logo-fallback{width:44px;height:44px;border-radius:7px;flex-shrink:0;background:linear-gradient(135deg,#0ea5e9,#6366f1);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff}
        .lbl-store-name{font-size:14px;font-weight:700;color:#fff;line-height:1.2}
        .lbl-store-sub{font-size:10px;color:#94a3b8;margin-top:2px}

        /* Body */
        .lbl-body{padding:14px 16px}

        /* Section label */
        .sec{font-size:9px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:5px;margin-top:12px}
        .sec:first-child{margin-top:0}

        /* Ship-to block */
        .ship-to-name{font-size:16px;font-weight:700;color:#0f172a;line-height:1.2;margin-bottom:4px}
        .ship-to-phone{font-size:12px;color:#334155;margin-bottom:6px;display:flex;align-items:center;gap:5px}
        .ship-to-addr{font-size:11.5px;color:#475569;line-height:1.6}

        /* Divider */
        .divider{border:none;border-top:1px dashed #cbd5e1;margin:12px 0}

        /* Order number */
        .order-no-row{display:flex;justify-content:space-between;align-items:flex-end;gap:8px}
        .order-no-block{}
        .order-no-val{font-family:ui-monospace,'SFMono-Regular',Menlo,monospace;font-size:13px;font-weight:700;color:#0f172a;letter-spacing:.03em}
        .tracking-val{font-family:ui-monospace,'SFMono-Regular',Menlo,monospace;font-size:10.5px;color:#475569}

        /* QR */
        .qr-block{display:flex;flex-direction:column;align-items:center;gap:3px;flex-shrink:0}
        #qrcode canvas,#qrcode img{width:60px!important;height:60px!important;border-radius:4px}
        .qr-label{font-size:8px;color:#94a3b8;text-align:center}

        /* Footer */
        .lbl-footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:8px 16px;display:flex;justify-content:space-between;align-items:center}
        .lbl-footer-date{font-size:9px;color:#94a3b8}
        .lbl-footer-items{font-size:10px;font-weight:600;color:#334155}

        /* Print */
        @media print{
            .toolbar{display:none!important}
            body{background:#fff}
            .page-wrap{padding:0;justify-content:flex-start}
            .label{box-shadow:none;border-radius:0;border-color:#000}
            @page{size:100mm 150mm;margin:0}
        }
    </style>
</head>
<body>

{{-- Toolbar --}}
<div class="toolbar">
    <div>
        <div class="toolbar-title">Shipping Label</div>
        <div class="toolbar-sub">#{{ $orderNumber }} · {{ $tenant['name'] ?? 'Store' }}</div>
    </div>
    <a href="{{ url()->previous() }}" class="tbtn" style="margin-left:auto">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back
    </a>
    <button type="button" class="tbtn" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Label
    </button>
    <button type="button" class="tbtn tbtn-primary" onclick="downloadPdf()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
    </button>
</div>

<div class="page-wrap">
    <div class="label" id="shipping-label">

        {{-- Header --}}
        <div class="lbl-header">
            @if(!empty($tenant['logo']))
                <img src="{{ $tenant['logo'] }}" alt="{{ $tenant['name'] }}" class="lbl-logo">
            @else
                <div class="lbl-logo-fallback">{{ strtoupper(substr($tenant['name'] ?? 'S', 0, 1)) }}</div>
            @endif
            <div>
                <div class="lbl-store-name">{{ $tenant['name'] ?? 'Store' }}</div>
                @if(!empty($tenant['phone']))<div class="lbl-store-sub">{{ $tenant['phone'] }}</div>@endif
                @if(!empty($tenant['domain']))<div class="lbl-store-sub">{{ $tenant['domain'] }}</div>@endif
            </div>
        </div>

        {{-- Body --}}
        <div class="lbl-body">

            <div class="sec">Ship To</div>
            <div class="ship-to-name">{{ $customerName }}</div>
            @if($customerPhone)
            <div class="ship-to-phone">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.55a16 16 0 0 0 6 6l.86-.86a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.5 16z"/></svg>
                {{ $customerPhone }}
            </div>
            @endif
            @php
                $addrLines = array_filter([
                    $address['address_line_1'] ?? $address['address'] ?? $address['street'] ?? null,
                    $address['address_line_2'] ?? $address['street2'] ?? null,
                    implode(', ', array_filter([$address['city'] ?? null, $address['state'] ?? $address['region'] ?? null])),
                    implode(' ', array_filter([$address['postal_code'] ?? $address['zip'] ?? null, $address['country'] ?? null])),
                ]);
            @endphp
            @if($addrLines)
            <div class="ship-to-addr">
                @foreach($addrLines as $line){{ $line }}<br>@endforeach
            </div>
            @endif

            <hr class="divider">

            <div class="order-no-row">
                <div class="order-no-block">
                    <div class="sec">Order #</div>
                    <div class="order-no-val">#{{ $orderNumber }}</div>
                    @if($tracking)
                    <div class="sec" style="margin-top:8px">Tracking</div>
                    <div class="tracking-val">{{ $tracking }}</div>
                    @endif
                    @php $itemCount = count($order['items'] ?? []); @endphp
                    @if($itemCount > 0)
                    <div class="sec" style="margin-top:8px">Items</div>
                    <div class="tracking-val">{{ $itemCount }} item{{ $itemCount !== 1 ? 's' : '' }}</div>
                    @endif
                    @if(!empty($order['created_at']))
                    <div class="sec" style="margin-top:8px">Date</div>
                    <div class="tracking-val">{{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}</div>
                    @endif
                </div>
                <div class="qr-block">
                    <div id="qrcode"></div>
                    <div class="qr-label">Scan to verify</div>
                </div>
            </div>

        </div>{{-- /lbl-body --}}

        {{-- Footer --}}
        <div class="lbl-footer">
            <span class="lbl-footer-date">Generated {{ now()->format('M d, Y') }}</span>
            <span class="lbl-footer-items">{{ $tenant['name'] ?? 'Store' }}</span>
        </div>

    </div>{{-- /label --}}
</div>

<script>
    var qrData = '{{ addslashes($orderNumber) }}';

    document.addEventListener('DOMContentLoaded', function () {
        try {
            new QRCode(document.getElementById('qrcode'), {
                text: qrData,
                width: 60,
                height: 60,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch(e) {}
    });

    function downloadPdf() {
        var el = document.getElementById('shipping-label');
        var fname = 'shipping-label-{{ addslashes($orderNumber) }}.pdf';
        if (typeof html2pdf === 'undefined') { window.print(); return; }
        html2pdf().set({
            margin: 0,
            filename: fname,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 3, useCORS: true, backgroundColor: '#ffffff', logging: false },
            jsPDF: { unit: 'mm', format: [100, 150], orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all'] }
        }).from(el).save();
    }
</script>
</body>
</html>
