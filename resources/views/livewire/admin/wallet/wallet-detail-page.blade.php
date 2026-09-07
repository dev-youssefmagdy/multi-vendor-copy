@php
    $row = is_array($row ?? null) ? $row : (array) ($row ?? []);
    $fmt = fn($v) => '$' . number_format((float) ($v ?? 0), 2);
@endphp

<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.wallets.index') }}">← Ledger</a>
            @if (($row['net_payable_to_tenant'] ?? 0) > 0)
                <a class="btn btn-primary" href="{{ route('admin.finance.payouts.create', $row['tenant_id'] ?? '') }}">
                    Pay Out — {{ $fmt($row['net_payable_to_tenant']) }}
                </a>
            @endif
        </div>
    </div>

    @if ($markSuccess)
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:rgba(0,229,155,.1);border:1px solid rgba(0,229,155,.2);color:var(--green);font-size:13px;font-weight:600;">
            ✓ Order marked as settled and settlement record created.
        </div>
    @endif

    <div class="page-stack section-gap">
        @include('livewire.admin.wallet.partials.tenant-ledger-modal', [
            'row' => $row,
        ])
    </div>

    {{-- ── Mark as Paid Modal ───────────────────────────────────────────────── --}}
    @if ($showMarkPaidModal)
        <div style="position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(3px);"
                wire:click="closeMarkPaid"></div>

            <div style="position:relative;z-index:1;width:100%;max-width:480px;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 28px 24px;">

                {{-- Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(0,229,155,.12);display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:15px;font-weight:700;color:var(--t1);">Mark Order as Settled</div>
                            <div style="font-size:11px;color:var(--t3);font-family:monospace;">{{ \Illuminate\Support\Str::limit($markOrderUuid, 28) }}</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeMarkPaid"
                        style="width:28px;height:28px;border-radius:7px;background:var(--elevated);border:1px solid var(--border);color:var(--t3);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;">✕</button>
                </div>

                {{-- Amount row --}}
                <div style="padding:12px 16px;border-radius:10px;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.18);display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <span style="font-size:12px;color:var(--t2);">Amount owed to central</span>
                    <span style="font-size:18px;font-weight:800;color:var(--amber);">${{ number_format($markAmount, 2) }}</span>
                </div>

                {{-- Error --}}
                @if ($markError)
                    <div style="margin-bottom:14px;padding:10px 14px;border-radius:8px;background:rgba(255,90,90,.1);border:1px solid rgba(255,90,90,.2);color:var(--red);font-size:12px;display:flex;align-items:flex-start;gap:8px;">
                        <span style="flex-shrink:0;line-height:1;">⚠</span>
                        <span>{{ $markError }}</span>
                    </div>
                @endif

                {{-- Method selection --}}
                <div style="margin-bottom:16px;">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--t3);display:block;margin-bottom:10px;">Payment Method</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;" wire:loading.class="op-disabled" wire:target="confirmMarkPaid">

                        <label style="cursor:pointer;position:relative;display:block;">
                            <input type="radio" wire:model.live="markMethod" value="manual" style="position:absolute;opacity:0;pointer-events:none;">
                            <div style="padding:12px 14px;border-radius:10px;border:2px solid {{ $markMethod === 'manual' ? 'var(--cyan)' : 'var(--border)' }};background:{{ $markMethod === 'manual' ? 'rgba(0,229,255,.07)' : 'var(--elevated)' }};transition:all .15s;position:relative;">
                                @if ($markMethod === 'manual')
                                    <div style="position:absolute;top:8px;right:8px;width:16px;height:16px;border-radius:50%;background:var(--cyan);color:#04141c;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;">✓</div>
                                @endif
                                <div style="font-size:16px;margin-bottom:4px;">🏦</div>
                                <div style="font-size:12px;font-weight:700;color:var(--t1);">Manual Payment</div>
                                <div style="font-size:10px;color:var(--t3);margin-top:2px;">Bank / cash / cheque received</div>
                            </div>
                        </label>

                        <label style="cursor:pointer;position:relative;display:block;">
                            <input type="radio" wire:model.live="markMethod" value="payout_offset" style="position:absolute;opacity:0;pointer-events:none;">
                            <div style="padding:12px 14px;border-radius:10px;border:2px solid {{ $markMethod === 'payout_offset' ? 'var(--green)' : 'var(--border)' }};background:{{ $markMethod === 'payout_offset' ? 'rgba(0,229,155,.07)' : 'var(--elevated)' }};transition:all .15s;position:relative;">
                                @if ($markMethod === 'payout_offset')
                                    <div style="position:absolute;top:8px;right:8px;width:16px;height:16px;border-radius:50%;background:var(--green);color:#04241c;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;">✓</div>
                                @endif
                                <div style="font-size:16px;margin-bottom:4px;">🔄</div>
                                <div style="font-size:12px;font-weight:700;color:var(--t1);">Offset from Payout</div>
                                <div style="font-size:10px;color:var(--t3);margin-top:2px;">Deduct from what you owe tenant</div>
                            </div>
                        </label>

                    </div>
                </div>

                {{-- Reference (required for manual) --}}
                <div wire:loading.remove wire:target="markMethod">
                    @if ($markMethod === 'manual')
                        <div style="margin-bottom:14px;">
                            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--t3);display:block;margin-bottom:6px;">
                                Payment Reference <span style="color:var(--red);">*</span>
                            </label>
                            <input type="text" wire:model="markReference"
                                placeholder="e.g. TXN-2026-0042, bank slip #, etc."
                                style="width:100%;padding:9px 12px;background:var(--elevated);border:1px solid {{ $markError ? 'var(--red)' : 'var(--border)' }};border-radius:8px;color:var(--t1);font-size:13px;box-sizing:border-box;">
                        </div>
                    @else
                        <div style="margin-bottom:14px;padding:10px 14px;border-radius:8px;background:rgba(0,229,155,.06);border:1px solid rgba(0,229,155,.15);font-size:12px;color:var(--t2);">
                            A reference will be auto-generated. The amount will be counted as settled against the payout balance you owe this tenant.
                        </div>
                    @endif
                </div>
                <div wire:loading wire:target="markMethod" style="margin-bottom:14px;padding:10px 14px;border-radius:8px;background:var(--elevated);border:1px solid var(--border);font-size:12px;color:var(--t3);">
                    Updating…
                </div>

                {{-- Optional note --}}
                <div style="margin-bottom:22px;">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--t3);display:block;margin-bottom:6px;">Note (optional)</label>
                    <input type="text" wire:model="markNote"
                        placeholder="Internal note…"
                        style="width:100%;padding:9px 12px;background:var(--elevated);border:1px solid var(--border);border-radius:8px;color:var(--t1);font-size:13px;box-sizing:border-box;">
                </div>

                {{-- Actions --}}
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" wire:click="closeMarkPaid" wire:loading.attr="disabled" wire:target="confirmMarkPaid" class="btn btn-secondary">Cancel</button>
                    <button type="button" wire:click="confirmMarkPaid" wire:loading.attr="disabled" wire:target="confirmMarkPaid" class="btn btn-primary"
                        style="background:var(--green);border-color:var(--green);">
                        <span wire:loading.remove wire:target="confirmMarkPaid">✓ Confirm Settlement</span>
                        <span wire:loading wire:target="confirmMarkPaid">Processing…</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

</main>
