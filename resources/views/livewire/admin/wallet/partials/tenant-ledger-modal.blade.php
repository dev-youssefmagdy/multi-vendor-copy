@php
    $row = is_array($row ?? null) ? $row : (array) ($row ?? []);
    $fmt = fn($v) => '$' . number_format((float) ($v ?? 0), 2);
    $unsettled = $row['unsettled_orders'] ?? [];
    $recent = $row['recent_paid_orders'] ?? [];

    $balance = (float) ($row['vendor_balance'] ?? 0);
    $balanceDir = $balance < 0 ? 'tenant-owes' : ($balance > 0 ? 'central-owes' : 'settled');
    $balanceLabel = $balance < 0 ? 'Tenant → Central' : ($balance > 0 ? 'Central → Tenant' : 'Settled');
    $balanceColor = $balance < 0 ? 'var(--red)' : ($balance > 0 ? 'var(--green)' : 'var(--t3)');
    $balanceGlow = $balance < 0 ? 'card-glow-red' : ($balance > 0 ? 'card-glow-green' : '');
@endphp

<div class="page-stack" style="gap:20px;">

    {{-- ── KPI strip ─────────────────────────────────────────────────────── --}}
    <div class="g-stats3">

        {{-- Direction card --}}
        <div class="card {{ $balanceGlow }}" style="border-color:{{ $balanceColor }}22;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="eyebrow">Settlement Direction</div>
                <div style="width:32px;height:32px;border-radius:9px;background:{{ $balanceColor }}18;display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $balanceColor }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value" style="color:{{ $balanceColor }};font-size:18px;margin-bottom:6px;">{{ $balanceLabel }}</div>
            <div class="stat-sub">Net balance: <strong style="color:{{ $balanceColor }};">{{ $fmt($row['vendor_balance'] ?? 0) }}</strong></div>
            <div class="stat-sub" style="margin-top:2px;">+ = central owes vendor</div>
        </div>

        {{-- Tenant Owes Central --}}
        <div class="card card-glow-amber" style="border-color:rgba(245,158,11,.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="eyebrow">Tenant Owes Central</div>
                <div style="width:32px;height:32px;border-radius:9px;background:rgba(245,158,11,.12);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value" style="color:var(--amber);font-size:18px;margin-bottom:6px;">{{ $fmt($row['tenant_owes_central'] ?? 0) }}</div>
            <div class="stat-sub">Unsettled vendor cost on vendor-gateway paid orders</div>
        </div>

        {{-- Central Owes Tenant --}}
        <div class="card card-glow-cyan" style="border-color:rgba(0,229,255,.12);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="eyebrow">Central Owes Tenant</div>
                <div style="width:32px;height:32px;border-radius:9px;background:rgba(0,229,255,.1);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value" style="color:var(--cyan);font-size:18px;margin-bottom:6px;">{{ $fmt($row['central_owes_tenant'] ?? 0) }}</div>
            <div class="stat-sub">Vendor profit on central-gateway orders minus payouts</div>
        </div>

    </div>

    {{-- ── Order Totals Breakdown ──────────────────────────────────────── --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(162,89,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a259ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>
                </svg>
            </div>
            <div>
                <h3 class="panel-title" style="margin:0;">Order Totals Breakdown</h3>
                <p class="panel-copy" style="margin:2px 0 0;">Paid orders — revenue, fees, and profit allocation</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            {{-- Revenue side --}}
            <div>
                <div class="eyebrow" style="margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border);">Revenue Inputs</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @php
                        $revenueRows = [
                            ['Subtotal (items)',    '+', $fmt($row['subtotal_total'] ?? 0),      'var(--t1)', true],
                            ['Items discount',      '−', $fmt($row['items_discount_total'] ?? 0), 'var(--red)', false],
                            ['Order discount',      '−', $fmt($row['discount_total'] ?? 0),       'var(--red)', false],
                            ['Items tax',           '+', $fmt($row['items_tax_total'] ?? 0),      'var(--t2)', false],
                            ['Order tax',           '+', $fmt($row['tax_total'] ?? 0),            'var(--t2)', false],
                            ['Shipping',            '+', $fmt($row['shipping_total'] ?? 0),       'var(--t2)', false],
                        ];
                    @endphp
                    @foreach ($revenueRows as [$label, $sign, $value, $color, $bold])
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                            <span style="font-size:12px;color:var(--t2);">{{ $label }}</span>
                            <span style="font-size:12px;font-weight:{{ $bold ? 700 : 500 }};color:{{ $color }};">{{ $sign !== '+' ? $sign . ' ' : '' }}{{ $value }}</span>
                        </div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 8px;border-radius:7px;background:rgba(0,229,255,.07);border:1px solid rgba(0,229,255,.15);margin-top:4px;">
                        <span style="font-size:12px;font-weight:700;color:var(--t1);">Collected Sales (Grand Total)</span>
                        <span style="font-size:13px;font-weight:800;color:var(--cyan);">{{ $fmt($row['collected_sales'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            {{-- Profit side --}}
            <div>
                <div class="eyebrow" style="margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border);">Profit Allocation</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                        <span style="font-size:12px;color:var(--t2);">Owner profit (commission)</span>
                        <span style="font-size:12px;font-weight:500;color:var(--t1);">{{ $fmt($row['owner_profit_total'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                        <span style="font-size:12px;color:var(--t2);">Vendor cost (catalog → central)</span>
                        <span style="font-size:12px;font-weight:500;color:var(--t1);">{{ $fmt($row['vendor_cost_total'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                        <span style="font-size:12px;color:var(--t2);">Vendor gateway fee</span>
                        <span style="font-size:12px;font-weight:500;color:var(--t1);">{{ $fmt($row['vendor_gateway_fee_total'] ?? 0) }}</span>
                    </div>

                    <div style="height:1px;background:var(--border);margin:4px 0;"></div>

                    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 8px;border-radius:7px;background:rgba(0,229,155,.07);border:1px solid rgba(0,229,155,.15);">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--t1);">Vendor Profit Total</div>
                            <div style="font-size:10px;color:var(--t3);">central-gw: grand_total − owner_profit</div>
                        </div>
                        <span style="font-size:13px;font-weight:800;color:var(--green);">{{ $fmt($row['vendor_profit_total'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 8px;border-radius:7px;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.15);">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--t1);">Central Profit Total</div>
                            <div style="font-size:10px;color:var(--t3);">vendor-gw: vendor_cost</div>
                        </div>
                        <span style="font-size:13px;font-weight:800;color:var(--amber);">{{ $fmt($row['central_profit_total'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 8px;border-radius:7px;background:var(--elevated);border:1px solid var(--border);">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--t1);">Vendor Balance</div>
                            <div style="font-size:10px;color:var(--t3);">vendor profit − central profit</div>
                        </div>
                        <span style="font-size:13px;font-weight:800;color:{{ $balanceColor }};">{{ $fmt($row['vendor_balance'] ?? 0) }}</span>
                    </div>

                    <div style="height:1px;background:var(--border);margin:4px 0;"></div>

                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                        <span style="font-size:12px;color:var(--t2);">Settlements from tenant</span>
                        <span style="font-size:12px;font-weight:500;color:var(--t1);">{{ $fmt($row['settlements_received'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:7px;background:var(--elevated);">
                        <span style="font-size:12px;color:var(--t2);">Payouts sent to tenant</span>
                        <span style="font-size:12px;font-weight:500;color:var(--t1);">{{ $fmt($row['payouts_sent'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Unsettled Paid Orders ─────────────────────────────────────────── --}}
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,90,90,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title" style="margin:0;">Unsettled Paid Orders</h3>
                    <p class="panel-copy" style="margin:2px 0 0;">Orders awaiting settlement from this tenant</p>
                </div>
            </div>
            @if (count($unsettled) > 0)
                <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(255,90,90,.12);color:var(--red);">{{ count($unsettled) }} pending</span>
            @endif
        </div>

        @if (empty($unsettled))
            <div style="display:flex;flex-direction:column;align-items:center;padding:28px 0;gap:8px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
                <p style="font-size:13px;color:var(--t3);margin:0;">All orders are settled — no outstanding amounts.</p>
            </div>
        @else
            <div class="tw">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="text-align:left;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px 0;">Order ID</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Grand Total</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Vendor Cost</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Gateway Fee</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 0 8px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unsettled as $o)
                            <tr style="border-bottom:1px solid var(--border)08;">
                                <td style="padding:9px 10px 9px 0;">
                                    <span style="font-family:monospace;font-size:11px;color:var(--t2);background:var(--elevated);padding:2px 6px;border-radius:4px;">
                                        {{ \Illuminate\Support\Str::limit($o['uuid'], 20) }}
                                    </span>
                                </td>
                                <td style="text-align:right;padding:9px 10px;font-size:13px;font-weight:600;color:var(--t1);">{{ $fmt($o['grand_total']) }}</td>
                                <td style="text-align:right;padding:9px 10px;font-size:12px;color:var(--amber);">{{ $fmt($o['vendor_cost']) }}</td>
                                <td style="text-align:right;padding:9px 10px;font-size:12px;color:var(--t2);">{{ $fmt($o['vendor_gateway_fee']) }}</td>
                                <td style="text-align:right;padding:9px 0;">
                                    <button type="button"
                                        wire:click="openMarkPaid({{ $o['id'] }}, '{{ $o['uuid'] }}', {{ $o['vendor_cost'] }})"
                                        style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;background:rgba(0,229,155,.1);border:1px solid rgba(0,229,155,.25);color:var(--green);cursor:pointer;white-space:nowrap;">
                                        ✓ Mark Paid
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Recent Paid Orders ────────────────────────────────────────────── --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(0,229,155,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9,11 12,14 22,4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <div>
                <h3 class="panel-title" style="margin:0;">Recent Paid Orders</h3>
                <p class="panel-copy" style="margin:2px 0 0;">Latest completed orders with profit breakdown</p>
            </div>
        </div>

        @if (empty($recent))
            <div style="display:flex;flex-direction:column;align-items:center;padding:28px 0;gap:8px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--t3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                <p style="font-size:13px;color:var(--t3);margin:0;">No paid orders yet for this tenant.</p>
            </div>
        @else
            <div class="tw">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="text-align:left;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px 0;">Order ID</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Grand Total</th>
                            <th style="text-align:center;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Gateway</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 10px 8px;">Vendor Profit</th>
                            <th style="text-align:right;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--t3);padding:0 0 8px;">Central Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $o)
                            <tr style="border-bottom:1px solid color-mix(in srgb, var(--border) 60%, transparent);">
                                <td style="padding:9px 10px 9px 0;">
                                    <span style="font-family:monospace;font-size:11px;color:var(--t2);background:var(--elevated);padding:2px 6px;border-radius:4px;">
                                        {{ \Illuminate\Support\Str::limit($o['uuid'], 20) }}
                                    </span>
                                </td>
                                <td style="text-align:right;padding:9px 10px;font-size:13px;font-weight:600;color:var(--t1);">{{ $fmt($o['grand_total']) }}</td>
                                <td style="text-align:center;padding:9px 10px;">
                                    @if (($o['gateway_source'] ?? 'vendor') === 'central')
                                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(0,229,155,.12);color:var(--green);">Central</span>
                                    @else
                                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(245,158,11,.12);color:var(--amber);">Vendor</span>
                                    @endif
                                </td>
                                <td style="text-align:right;padding:9px 10px;font-size:12px;font-weight:600;color:var(--green);">
                                    {!! $o['vendor_profit'] > 0 ? $fmt($o['vendor_profit']) : '<span style="color:var(--t3)">—</span>' !!}
                                </td>
                                <td style="text-align:right;padding:9px 0;font-size:12px;font-weight:600;color:var(--amber);">
                                    {!! $o['central_profit'] > 0 ? $fmt($o['central_profit']) : '<span style="color:var(--t3)">—</span>' !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Payout action ─────────────────────────────────────────────────── --}}
    @if (($row['net_payable_to_tenant'] ?? 0) > 0)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-radius:12px;background:rgba(0,229,155,.06);border:1px solid rgba(0,229,155,.2);">
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--t1);margin-bottom:3px;">Payout Ready</div>
                <div style="font-size:12px;color:var(--t3);">Net amount payable to this tenant</div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="font-size:20px;font-weight:800;color:var(--green);">${{ number_format($row['net_payable_to_tenant'], 2) }}</div>
                <a class="btn btn-primary" href="{{ route('admin.finance.payouts.create', $row['tenant_id'] ?? '') }}" style="white-space:nowrap;">
                    Create Payout
                </a>
            </div>
        </div>
    @endif

</div>
