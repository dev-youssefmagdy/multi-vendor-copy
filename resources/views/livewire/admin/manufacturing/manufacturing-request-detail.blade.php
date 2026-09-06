<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Commerce · Manufacturing</div>
            <h1 class="D page-title">Request #{{ $request->id }} — {{ $request->product_name }}</h1>
            <p class="page-copy">Review details, manage payment requests, and communicate with the vendor.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.manufacturing.index') }}" class="btn btn-secondary">← Back to List</a>
        </div>
    </div>

    {{-- ── Details + Status ─────────────────────────────────────────────── --}}
    <div class="g-2col section-gap" style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

        {{-- Left: Request Details --}}
        <div class="card fu d1" style="padding:24px;">
            <h3 class="panel-title" style="margin-bottom:16px;">Request Details</h3>
            <dl style="display:grid;grid-template-columns:140px 1fr;gap:10px 16px;font-size:13px;">
                <dt class="entity-subtitle">Tenant</dt>
                <dd class="entity-title">{{ $request->tenant?->name ?? $request->tenant_id }}</dd>

                <dt class="entity-subtitle">Product</dt>
                <dd class="entity-title">
                    @if ($request->product_id)
                        <a href="{{ route('admin.products.edit', $request->product_id) }}"
                           class="text-cyan-400 hover:underline hover:text-cyan-300 transition-colors"
                           title="Edit product">
                            {{ $request->product_name }}
                            <svg style="display:inline;margin-left:4px;vertical-align:middle"
                                 width="12" height="12" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                    @else
                        {{ $request->product_name }}
                    @endif
                </dd>

                <dt class="entity-subtitle">Quantity</dt>
                <dd class="entity-title">{{ number_format($request->quantity) }}</dd>

                <dt class="entity-subtitle">Status</dt>
                <dd><span class="{{ $request->status->badgeClass() }}">{{ $request->status->label() }}</span></dd>

                <dt class="entity-subtitle">Submitted</dt>
                <dd class="entity-subtitle">{{ $request->created_at->format('M d, Y H:i') }}</dd>

                @if ($request->description)
                    <dt class="entity-subtitle" style="padding-top:4px;">Description</dt>
                    <dd class="entity-subtitle" style="white-space:pre-wrap;">{{ $request->description }}</dd>
                @endif

                @if ($request->admin_notes)
                    <dt class="entity-subtitle" style="padding-top:4px;">Admin Notes</dt>
                    <dd class="entity-subtitle" style="white-space:pre-wrap;">{{ $request->admin_notes }}</dd>
                @endif
            </dl>
        </div>

        {{-- Right: Status Update --}}
        @if ($canManage)
            <div class="card fu d2" style="padding:24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:16px;">
                    <h3 class="panel-title" style="margin:0;">Update Status</h3>
                    <span class="{{ $request->status->badgeClass() }}">{{ $request->status->label() }}</span>
                </div>
                <div class="page-stack">
                    <div>
                        <label class="field-label">New Status</label>
                        <x-select wire:model.defer="newStatus">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </x-select>
                        @error('newStatus') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Admin Notes <span class="field-optional">(visible to
                                vendor)</span></label>
                        <x-textarea wire:model.defer="adminNotes" rows="3"
                            placeholder="Add internal notes or instructions for the vendor..." />
                        @error('adminNotes') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <x-btn wire:click="updateStatus" wire:loading.attr="disabled" wire:target="updateStatus" class="w-full">
                        <svg wire:loading.remove wire:target="updateStatus" width="14" height="14" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            style="margin-right:5px;vertical-align:-1px;">
                            <path d="M22 2L11 13" />
                            <path d="M22 2L15 22l-4-9-9-4 20-7z" />
                        </svg>
                        <span wire:loading.remove wire:target="updateStatus">Save &amp; Notify Tenant</span>
                        <span wire:loading wire:target="updateStatus">Saving…</span>
                    </x-btn>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Payment Requests ─────────────────────────────────────────────── --}}
    <section class="card fu d3 table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Payment Requests</h3>
                <p class="panel-copy">Issue payment requests to the vendor. They can pay using the available payment
                    gateways.</p>
            </div>
            @if ($canManage)
                <div class="page-actions compact-actions">
                    <x-btn type="button" wire:click="togglePaymentForm">
                        {{ $showPaymentForm ? 'Cancel' : '+ New Payment Request' }}
                    </x-btn>
                </div>
            @endif
        </div>

        @if ($showPaymentForm && $canManage)
            <div class="locale-fields-group fu" style="margin:0 0 20px 0;">
                <div class="locale-badge" style="margin-bottom:12px;">New Payment Request</div>
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">Label <span class="field-required">*</span></label>
                        <x-input wire:model.defer="paymentLabel" placeholder="e.g. Deposit – 50%, Final Balance..." />
                        @error('paymentLabel') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 90px;gap:8px;">
                        <div>
                            <label class="field-label">Amount <span class="field-required">*</span></label>
                            <x-input type="number" wire:model.defer="paymentAmount" min="0.01" step="0.01"
                                placeholder="0.00" />
                            @error('paymentAmount') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Currency</label>
                            <x-input wire:model.defer="paymentCurrency" placeholder="USD" maxlength="10" />
                        </div>
                    </div>
                    <div class="form-col-full">
                        <label class="field-label">Notes <span class="field-optional">(optional)</span></label>
                        <x-textarea wire:model.defer="paymentNotes" rows="2"
                            placeholder="Additional details for this payment..." />
                    </div>
                </div>
                <div class="page-actions compact-actions" style="margin-top:12px;justify-content:flex-end;">
                    <x-btn type="button" variant="secondary" wire:click="togglePaymentForm">Cancel</x-btn>
                    <x-btn type="button" wire:click="createPaymentRequest" wire:loading.attr="disabled"
                        wire:target="createPaymentRequest">
                        <span wire:loading.remove wire:target="createPaymentRequest">Create & Notify Tenant</span>
                        <span wire:loading wire:target="createPaymentRequest">Creating...</span>
                    </x-btn>
                </div>
            </div>
        @endif

        @if ($paymentRequests->isNotEmpty())
            <x-table :headers="['#', 'Label', 'Amount', 'Status', 'Gateway', 'Paid At', 'Notes', 'Actions']">
                @foreach ($paymentRequests as $pr)
                    <tr wire:key="pr-{{ $pr->id }}">
                        <td><span class="entity-subtitle">#{{ $pr->id }}</span></td>
                        <td>
                            <div class="entity-title">{{ $pr->label }}</div>
                        </td>
                        <td>
                            <div class="entity-title">{{ $pr->currency }} {{ number_format((float) $pr->amount, 2) }}</div>
                        </td>
                        <td><span class="{{ $pr->status->badgeClass() }}">{{ $pr->status->label() }}</span></td>
                        <td><span class="entity-subtitle">{{ $pr->gateway_code ? strtoupper($pr->gateway_code) : '—' }}</span>
                        </td>
                        <td><span class="entity-subtitle">{{ $pr->paid_at ? $pr->paid_at->format('M d, Y') : '—' }}</span></td>
                        <td>
                            @if ($pr->notes)
                                <span class="entity-subtitle"
                                    style="max-width:160px;overflow:hidden;text-overflow:ellipsis;display:block;white-space:nowrap;"
                                    title="{{ $pr->notes }}">
                                    {{ $pr->notes }}
                                </span>
                            @else
                                <span class="entity-subtitle">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($canManage && $pr->status->value === 'pending')
                                <x-btn type="button" variant="secondary" class="xs btn-danger"
                                    wire:click="cancelPaymentRequest({{ $pr->id }})" wire:confirm="Cancel this payment request?">
                                    Cancel
                                </x-btn>
                            @else
                                <span class="entity-subtitle">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="empty-state">
                <div class="empty-state-title">No payment requests yet</div>
                <p class="empty-state-copy">Create a payment request to ask the vendor for payment.</p>
            </div>
        @endif
    </section>

    {{-- ── Chat ──────────────────────────────────────────────────────────── --}}
    <section class="card fu d4 section-gap" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="padding:16px 20px;">
            <div>
                <h3 class="panel-title">Messages</h3>
                <p class="panel-copy">Chat with the vendor about this manufacturing request.</p>
            </div>
        </div>

        {{-- Messages list --}}
        <div id="chat-messages"
            style="max-height:420px;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px;"
            x-data x-init="$el.scrollTop = $el.scrollHeight"
            @chat-scrolled.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 80)">
            @forelse ($messages as $msg)
                <div wire:key="msg-{{ $msg->id }}"
                    style="display:flex;flex-direction:column;align-items:{{ $msg->sender_type === 'admin' ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width:75%;padding:10px 14px;border-radius:12px;font-size:13px;line-height:1.5;
                                        background:{{ $msg->sender_type === 'admin' ? 'var(--accent)' : 'var(--elevated)' }};
                                        color:{{ $msg->sender_type === 'admin' ? '#fff' : 'var(--t1)' }};">
                        {{ $msg->message }}
                    </div>
                    <div style="font-size:11px;color:var(--t3);margin-top:3px;padding:0 4px;">
                        {{ $msg->sender_name }} · {{ $msg->created_at->format('M d, H:i') }}
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px 0;">
                    <div class="empty-state-title">No messages yet</div>
                    <p class="empty-state-copy">Start a conversation with the vendor.</p>
                </div>
            @endforelse
        </div>

        @if ($canManage)
            {{-- Message input --}}
            <div style="border-top:1px solid var(--border);padding:14px 20px;">
                <div style="display:flex;gap:10px;align-items:flex-end;">
                    <div style="flex:1;">
                        <x-textarea wire:model.defer="chatMessage" rows="2" placeholder="Type a message to the vendor…"
                            style="resize:none;" x-on:keydown.ctrl.enter="$wire.sendMessage()" />
                        @error('chatMessage') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <x-btn type="button" wire:click="sendMessage" wire:loading.attr="disabled" wire:target="sendMessage"
                        style="align-self:flex-end;">
                        <span wire:loading.remove wire:target="sendMessage">Send</span>
                        <span wire:loading wire:target="sendMessage">…</span>
                    </x-btn>
                </div>
                <p class="entity-subtitle" style="margin-top:6px;">Ctrl+Enter to send</p>
            </div>
        @endif
    </section>
</main>

@script
<script>
    if (window.Echo) {
        window.Echo.private('tenant.{{ $request->tenant_id }}.manufacturing.{{ $request->id }}')
            .listen('.message.sent', (e) => {
                if (e.sender_type === 'tenant') {
                    $wire.$refresh();
                }
            });
    }
</script>
@endscript