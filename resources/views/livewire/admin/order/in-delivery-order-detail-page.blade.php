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
            <a class="btn btn-secondary" href="{{ route('admin.shipping.in-delivery') }}">← Delivery Queue</a>
            <a class="btn btn-secondary" target="_blank"
                href="{{ route('admin.orders.shipping-label', ['tenantId' => $tenantId, 'orderNumber' => $orderNumber]) }}"
                style="display:inline-flex;align-items:center;gap:7px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="17"/><line x1="9.5" y1="14.5" x2="14.5" y2="14.5"/>
                </svg>
                Shipping Label
            </a>
            <button type="button" class="btn btn-secondary" wire:click="$set('showReturnModal', true)"
                style="display:inline-flex;align-items:center;gap:7px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
                </svg>
                Create Return
            </button>
            <a class="btn btn-primary" target="_blank"
                href="{{ route('admin.orders.receipt', ['tenantId' => $tenantId, 'orderNumber' => $orderNumber]) }}"
                style="display:inline-flex;align-items:center;gap:7px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/>
                </svg>
                View Receipt
            </a>
        </div>
    </div>

    {{-- Status controls --}}
    <div class="card fu d2 section-gap">
        <h4 class="panel-title">Status Controls</h4>
        <div class="form-grid form-grid-2" style="align-items:flex-end;gap:12px">
            <div>
                <label class="field-label">Shipping Status</label>
                <x-select wire:model.defer="selectedShippingStatus">
                    @foreach ($shippingStatusOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-btn type="button" wire:click="updateShippingStatus">Update Shipping Status</x-btn>
            </div>
            <div>
                <label class="field-label">Order Status</label>
                <x-select wire:model.defer="selectedOrderStatus">
                    @foreach ($orderStatusOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-btn type="button" wire:click="updateOrderStatus">Update Order Status</x-btn>
            </div>
        </div>
        {{-- Tracking number row --}}
        <div style="grid-column:1/-1;border-top:1px solid var(--border);padding-top:16px;margin-top:4px">
            <div class="eyebrow" style="margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    style="color:var(--cyan)">
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <line x1="7" y1="8" x2="7" y2="16" />
                    <line x1="10" y1="8" x2="10" y2="16" />
                    <line x1="14" y1="8" x2="14" y2="16" />
                    <line x1="17" y1="8" x2="17" y2="16" />
                </svg>
                Tracking Number
            </div>
            @php $savedTracking = $order['tracking_number'] ?? null; @endphp
            @if ($savedTracking)
                <div
                    style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;background:var(--elevated);border:1px solid var(--border);margin-bottom:10px">
                    <code
                        style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px;color:var(--t1);letter-spacing:.06em;word-break:break-all">{{ $savedTracking }}</code>
                    <button type="button" x-data="{copied:false}"
                        x-on:click="navigator.clipboard.writeText('{{ $savedTracking }}');copied=true;setTimeout(()=>copied=false,2000)"
                        style="margin-left:auto;flex-shrink:0;background:transparent;border:1px solid var(--border);border-radius:6px;padding:3px 9px;font-size:11px;cursor:pointer;color:var(--t2)"
                        x-bind:style="copied ? 'color:var(--cyan)' : ''">
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" x-cloak>Copied ✓</span>
                    </button>
                </div>
            @endif
            <div style="display:flex;gap:10px;align-items:flex-end">
                <div style="flex:1">
                    <label class="field-label">{{ $savedTracking ? 'Update' : 'Enter Tracking Number' }}</label>
                    <div style="position:relative">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8"
                            style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--t3);pointer-events:none">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <line x1="7" y1="8" x2="7" y2="16" />
                            <line x1="10" y1="8" x2="10" y2="16" />
                            <line x1="14" y1="8" x2="14" y2="16" />
                            <line x1="17" y1="8" x2="17" y2="16" />
                        </svg>
                        <input type="text" wire:model.defer="trackingNumber" class="field-control"
                            style="padding-left:36px;font-family:ui-monospace,SFMono-Regular,monospace;letter-spacing:.04em"
                            placeholder="e.g. 1Z999AA10123456784" autocomplete="off" spellcheck="false">
                    </div>
                </div>
                <x-btn type="button" wire:click="updateTrackingNumber" wire:loading.attr="disabled"
                    wire:target="updateTrackingNumber">
                    <span wire:loading.remove wire:target="updateTrackingNumber">Save</span>
                    <span wire:loading wire:target="updateTrackingNumber">Saving…</span>
                </x-btn>
            </div>
        </div>
    </div>

    <div class="page-stack section-gap">
        @include('livewire.admin.order.partials.order-details', [
            'order' => $order,
            'canManageShipping' => false,
            'showAttachments' => true,
            'orderAttachments' => $orderAttachments,
            'shippingStatusOptions' => $shippingStatusOptions,
        ])
    </div>

    {{-- Return Request Modal --}}
    @if($showReturnModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:480px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:4px">Create Return Request</h3>
            <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
                Order #{{ $orderNumber }} · {{ $order['tenant']['name'] ?? '' }}
            </p>
            <div style="margin-bottom:14px">
                <label class="field-label">Return Reason <span style="color:var(--red)">*</span></label>
                <textarea wire:model.defer="returnReason" rows="3" class="input"
                    placeholder="Describe why the customer is returning this order…"
                    style="resize:vertical"></textarea>
                @error('returnReason')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:20px">
                <label class="field-label">Customer Notes (optional)</label>
                <textarea wire:model.defer="returnNotes" rows="2" class="input"
                    placeholder="Any additional notes from the customer…"
                    style="resize:vertical"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" wire:click="$set('showReturnModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="createReturn">Submit Return</x-btn>
            </div>
        </div>
    </div>
    @endif
</main>
