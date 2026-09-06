{{-- Inline SVG mini-icons for each payment method type --}}
@props(['icon' => 'generic'])
@php $s = '12'; @endphp
@switch($icon)
    @case('card')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        @break
    @case('visa')
        <span style="font-size:8px;font-weight:900;color:#1a1f71;letter-spacing:-0.5px;">VISA</span>
        @break
    @case('mastercard')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 38 24"><circle cx="15" cy="12" r="10" fill="#EB001B"/><circle cx="23" cy="12" r="10" fill="#F79E1B"/><path d="M19 5a10 10 0 0 1 0 14 10 10 0 0 1 0-14z" fill="#FF5F00"/></svg>
        @break
    @case('amex')
        <span style="font-size:8px;font-weight:900;color:#006fcf;letter-spacing:-0.5px;">AMEX</span>
        @break
    @case('apple_pay')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.4c1.3.07 2.22.74 2.98.8 1.12-.24 2.2-.93 3.38-.84 1.44.12 2.53.72 3.24 1.84-3.02 1.84-2.27 5.88.2 7.04-.57 1.38-1.31 2.75-1.8 4.04zM13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
        @break
    @case('google_pay')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24"><text y="14" font-size="9" font-weight="700" fill="#4285F4">G</text><text x="6" y="14" font-size="9" font-weight="700" fill="#EA4335">p</text><text x="10" y="14" font-size="9" font-weight="700" fill="#FBBC04">a</text><text x="14" y="14" font-size="9" font-weight="700" fill="#34A853">y</text></svg>
        @break
    @case('mada')
        <span style="font-size:8px;font-weight:900;color:#006F51;">mada</span>
        @break
    @case('stc_pay')
        <span style="font-size:8px;font-weight:900;color:#6D1FEE;">STC</span>
        @break
    @case('knet')
        <span style="font-size:8px;font-weight:900;color:#00539B;">KNET</span>
        @break
    @case('benefit')
        <span style="font-size:8px;font-weight:900;color:#CB2026;">BENEFIT</span>
        @break
    @case('upi')
        <span style="font-size:8px;font-weight:900;color:#097939;">UPI</span>
        @break
    @case('pix')
        <span style="font-size:8px;font-weight:900;color:#32BCAD;">PIX</span>
        @break
    @case('paypal')
        <svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="#003087"><path d="M20.067 8.478c.492.315.844.825.974 1.42.342 1.576-.594 3.07-2.16 3.485a3.5 3.5 0 0 1-.847.1h-2.35l-.588 3.722H13l1.78-11.3h4.08c1.005 0 1.74.237 2.207.573zM8.933 4.9H5.2L3 16.9h1.985l.489-3.1h2.5c2.8 0 4.5-1.5 4.5-4.2 0-2.3-1.5-3.7-3.54-3.7z"/></svg>
        @break
    @case('klarna')
        <span style="font-size:8px;font-weight:900;color:#FFB3C7;background:#000;padding:0 2px;border-radius:2px;">K</span>
        @break
    @case('bnpl')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 8v4l3 3M3.055 11A9 9 0 1 0 21 12"/></svg>
        @break
    @case('bank')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M8 10v11M12 10v11M16 10v11M20 10v11"/></svg>
        @break
    @case('mobile_money')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
        @break
    @case('wallet')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM16 13a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/><path d="M20 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2"/></svg>
        @break
    @case('qr')
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><path d="M17 14v3M20 17h-3M17 20h3"/></svg>
        @break
    @default
        <svg width="{{ $s }}" height="{{ $s }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
@endswitch
