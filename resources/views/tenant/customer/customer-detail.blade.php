@extends('layouts.tenant')

@section('content')
@php
    $orders     = $detail['orders']     ?? [];
    $addresses  = $detail['addresses']  ?? collect();
    $totalSpent = $detail['totalSpent'] ?? 0;
    $paidSpent  = $detail['paidSpent']  ?? 0;
    $orderCount = $detail['orderCount'] ?? 0;
    $paidCount  = $detail['paidCount']  ?? 0;
    $avgOrder   = $detail['avgOrder']   ?? 0;
    $lastOrderAt= $detail['lastOrderAt']?? null;

    $tabs = [
        'profile'   => 'Profile',
        'orders'    => 'Orders (' . $orderCount . ')',
        'payments'  => 'Payments',
        'addresses' => 'Addresses (' . $addresses->count() . ')',
    ];

    $initialTab = old('_tab', $activeTab);
@endphp

<main id="mn" x-data="{ tab: '{{ $initialTab }}' }">

    {{-- ── Page head ──────────────────────────────────────────────────────── --}}
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $customer->full_name ?: 'Customer Detail' }}</h1>
                <span class="page-badge">CRM</span>
            </div>
            <p class="page-copy">Full customer profile — orders, payment history, saved addresses and inline editing.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">← Customers</a>
        </div>
    </div>

    {{-- ── Summary cards ───────────────────────────────────────────────────── --}}
    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total Orders</div>
                    <div class="D stat-value">{{ $orderCount }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="panel-copy">{{ $paidCount }} paid</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total Spent</div>
                    <div class="D stat-value">${{ number_format($totalSpent, 2) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="panel-copy">${{ number_format($paidSpent, 2) }} collected</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Avg Order</div>
                    <div class="D stat-value">${{ number_format($avgOrder, 2) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="panel-copy">Avg per order placed</p>
        </div>
        <div class="card">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Last Order</div>
                    <div class="D stat-value" style="font-size:18px;">{{ $lastOrderAt ? $lastOrderAt->format('M d, Y') : 'Never' }}</div>
                </div>
                <div class="mini-stat-dot {{ $customer->active ? 'dot-green' : 'dot-amber' }}"></div>
            </div>
            <p class="panel-copy">
                <span class="badge {{ $customer->active ? 'badge-green' : 'badge-amber' }}">
                    {{ $customer->active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>
    </div>

    {{-- ── Tab bar ──────────────────────────────────────────────────────────── --}}
    <div class="page-tabs section-gap" style="border-bottom: 1px solid var(--border); display:flex; gap:0; flex-wrap:wrap;">
        @foreach ($tabs as $key => $label)
            <button type="button"
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'tab-pill--active' : ''"
                class="tab-pill"
                :style="tab === '{{ $key }}'
                    ? 'padding:10px 20px;font-size:13px;font-weight:500;border:none;border-bottom:2px solid var(--accent, #6366f1);color:var(--accent, #6366f1);background:transparent;cursor:pointer;transition:color .15s, border-color .15s;'
                    : 'padding:10px 20px;font-size:13px;font-weight:500;border:none;border-bottom:2px solid transparent;color:var(--text-muted, #9ca3af);background:transparent;cursor:pointer;transition:color .15s, border-color .15s;'">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════ TAB: PROFILE ════════════════════════════════ --}}
    <div x-show="tab === 'profile'" x-cloak>
        <div class="page-stack section-gap">
            <form method="POST" action="{{ route('tenant.customers.update', $customerId) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="profile">

                <x-card-collapse title="Profile Details" subtitle="Update customer name, contact info, status and password." :start-open="true">
                    <div class="form-grid form-grid-2"
                         x-data="{
                             countryId: '{{ old('countryId', $customer->country_id ?? '') }}',
                             cities: {{ $cities->map(fn($n, $id) => ['id' => $id, 'name' => $n])->values()->toJson() }},
                             async onCountryChange(val) {
                                 this.countryId = val;
                                 this.cities = [];
                                 if (!val) { return; }
                                 const r = await fetch('{{ url('cities-by-country') }}/' + val);
                                 this.cities = await r.json();
                             }
                         }">
                        <div>
                            <label class="field-label">Full Name <span class="field-required">*</span></label>
                            <x-input name="fullName" value="{{ old('fullName', $customer->full_name) }}" placeholder="Full name" />
                            @error('fullName') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Email <span class="field-required">*</span></label>
                            <x-input type="email" name="email" value="{{ old('email', $customer->email) }}" placeholder="customer@example.com" />
                            @error('email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Phone</label>
                            <x-input type="tel" name="phone" data-phone-input value="{{ old('phone', $phone) }}" placeholder="+1 555 000 0000" />
                            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Country</label>
                            <select class="field-control" name="countryId" @change="onCountryChange($event.target.value)">
                                <option value="">— select country —</option>
                                @foreach ($countries as $cId => $cName)
                                    <option value="{{ $cId }}" :selected="countryId == '{{ $cId }}'">{{ $cName }}</option>
                                @endforeach
                            </select>
                            @error('countryId') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">City</label>
                            <select class="field-control" name="cityId" :disabled="!countryId">
                                <option value="">— select city —</option>
                                <template x-for="city in cities" :key="city.id">
                                    <option :value="city.id"
                                        :selected="city.id == '{{ old('cityId', $customer->city_id ?? '') }}'"
                                        x-text="city.name"></option>
                                </template>
                            </select>
                            @error('cityId') <p class="field-error">{{ $message }}</p> @enderror
                            <p x-show="!countryId" style="font-size:11px; color:var(--t3); margin-top:3px;">Select a country first</p>
                            <p x-show="countryId && cities.length === 0" x-cloak style="font-size:11px; color:var(--t3); margin-top:3px;">No cities for this country yet</p>
                        </div>
                        <div class="form-grid-full">
                            <label class="field-label">Default Address</label>
                            <x-input name="address" value="{{ old('address', $customer->address) }}" placeholder="Short freeform address" />
                            @error('address') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">New Password <span class="field-optional">(leave blank to keep)</span></label>
                            <x-input type="password" name="password" placeholder="Min 8 characters" autocomplete="new-password" />
                            @error('password') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Confirm Password</label>
                            <x-input type="password" name="passwordConfirmation" placeholder="Repeat password" autocomplete="new-password" />
                            @error('passwordConfirmation') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div style="display:flex; align-items:flex-end; padding-bottom:4px;">
                            <label class="toggle-field" style="cursor:pointer; display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" name="active" value="1" {{ old('active', $customer->active) ? 'checked' : '' }}>
                                <span class="field-label" style="margin:0;">Active account</span>
                            </label>
                        </div>
                    </div>

                    <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </x-card-collapse>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════ TAB: ORDERS ═════════════════════════════════ --}}
    <div x-show="tab === 'orders'" x-cloak>
        <div class="page-stack section-gap">
            @if (count($orders) === 0)
                <div class="card">
                    <div class="empty-state" style="padding:32px 0;">
                        <div class="empty-state-title">No orders yet</div>
                        <p class="empty-state-copy">This customer has not placed any orders.</p>
                    </div>
                </div>
            @else
                @foreach ($orders as $order)
                    @php
                        $fin    = $order['financials'] ?? [];
                        $status = $order['status'];
                        $statusLabel = $status instanceof \App\Enums\OrderStatus ? $status->label() : (string) $status;
                        $statusClass = match (true) {
                            $status === \App\Enums\OrderStatus::Completed || $status === \App\Enums\OrderStatus::Delivered => 'badge-green',
                            $status === \App\Enums\OrderStatus::Shipped || $status === \App\Enums\OrderStatus::Processing  => 'badge-cyan',
                            $status === \App\Enums\OrderStatus::Cancelled || $status === \App\Enums\OrderStatus::Rejected  => 'badge-amber',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <x-card-collapse
                        title="#{{ $order['uuid'] ?? $order['id'] }}"
                        subtitle="{{ $statusLabel }} · {{ $order['paid'] ? 'Paid' : 'Unpaid' }} · ${{ number_format((float)($fin['grand_total'] ?? 0), 2) }}"
                        :start-open="false">

                        <div class="details-grid">
                            <section class="details-panel">
                                <div class="details-header">
                                    <h4 class="panel-title">Order Info</h4>
                                </div>
                                <div class="details-list">
                                    <div class="details-kv">
                                        <span class="details-label">Status</span>
                                        <span class="details-value"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                                    </div>
                                    <div class="details-kv">
                                        <span class="details-label">Payment</span>
                                        <span class="details-value">
                                            <span class="badge {{ $order['paid'] ? 'badge-green' : 'badge-amber' }}">
                                                {{ $order['paid'] ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="details-kv">
                                        <span class="details-label">Gateway</span>
                                        <span class="details-value">{{ $order['gateway'] ?? '-' }}</span>
                                    </div>
                                    <div class="details-kv">
                                        <span class="details-label">Placed At</span>
                                        <span class="details-value">{{ optional($order['created_at'])->format('M d, Y H:i') ?? '-' }}</span>
                                    </div>
                                    <div class="details-kv">
                                        <span class="details-label">
                                            <a href="{{ route('tenant.orders.show', $order['id']) }}" class="btn btn-secondary btn-sm" style="margin-top:6px;">View Full Order →</a>
                                        </span>
                                    </div>
                                </div>
                            </section>

                            <section class="details-panel">
                                <div class="details-header">
                                    <h4 class="panel-title">Financials</h4>
                                </div>
                                <div class="details-list">
                                    <div class="details-kv"><span class="details-label">Subtotal</span><span class="details-value">${{ number_format((float)($fin['subtotal'] ?? 0), 2) }}</span></div>
                                    <div class="details-kv"><span class="details-label">Discount</span><span class="details-value">−${{ number_format((float)(($fin['items_discount'] ?? 0) + ($fin['discount'] ?? 0)), 2) }}</span></div>
                                    <div class="details-kv"><span class="details-label">Tax</span><span class="details-value">+${{ number_format((float)(($fin['items_tax'] ?? 0) + ($fin['tax'] ?? 0)), 2) }}</span></div>
                                    <div class="details-kv"><span class="details-label">Shipping</span><span class="details-value">+${{ number_format((float)($fin['shipping_total'] ?? 0), 2) }}</span></div>
                                    <div class="details-kv"><span class="details-label">Grand Total</span><span class="details-value" style="font-weight:600;">${{ number_format((float)($fin['grand_total'] ?? 0), 2) }}</span></div>
                                </div>
                            </section>

                            <section class="details-panel full">
                                <div class="details-header">
                                    <h4 class="panel-title">Line Items</h4>
                                </div>
                                <div class="tw">
                                    <table class="details-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Variant</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($order['items'] as $item)
                                                <tr>
                                                    <td><div class="details-inline-title">{{ $item['name'] }}</div></td>
                                                    <td>{{ $item['variant'] ?? '—' }}</td>
                                                    <td>{{ $item['qty'] }}</td>
                                                    <td>${{ number_format((float)($item['price'] ?? 0), 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4"><div class="json-empty">No items.</div></td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </x-card-collapse>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ═══════════════════════ TAB: PAYMENTS ═══════════════════════════════ --}}
    <div x-show="tab === 'payments'" x-cloak>
        <div class="page-stack section-gap">
            <section class="card table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <div class="panel-title">Payment History</div>
                        <p class="panel-copy">All orders with payment status and amounts for this customer.</p>
                    </div>
                </div>
                <x-table :headers="['Order', 'Gateway', 'Amount', 'Status', 'Date']">
                    @forelse ($orders as $order)
                        @php
                            $fin    = $order['financials'] ?? [];
                            $grand  = $fin['grand_total'] ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('tenant.orders.show', $order['id']) }}" class="entity-title" style="color:var(--accent,#6366f1);">
                                    #{{ $order['uuid'] ?? $order['id'] }}
                                </a>
                            </td>
                            <td><div class="entity-subtitle">{{ $order['gateway'] ?? '-' }}</div></td>
                            <td><div class="entity-title">${{ number_format((float)$grand, 2) }}</div></td>
                            <td>
                                <span class="badge {{ $order['paid'] ? 'badge-green' : 'badge-amber' }}">
                                    {{ $order['paid'] ? 'Paid' : 'Unpaid' }}
                                </span>
                            </td>
                            <td><div class="entity-subtitle">{{ optional($order['created_at'])->format('M d, Y') ?? '-' }}</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state" style="padding:24px 0;">
                                    <div class="empty-state-title">No payments</div>
                                    <p class="empty-state-copy">No payment records exist for this customer.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </section>
        </div>
    </div>

    {{-- ═══════════════════════ TAB: ADDRESSES ══════════════════════════════ --}}
    <div x-show="tab === 'addresses'" x-cloak
         x-data="{
             modalOpen: false,
             editId: null,
             addr: { label:'', fullName:'', email:'', phone:'', line1:'', city:'', state:'', country:'', isDefault: false },
             open(address) {
                 this.editId = address ? address.id : null;
                 this.addr   = address ? { ...address } : { label:'', fullName:'', email:'', phone:'', line1:'', city:'', state:'', country:'', isDefault: false };
                 this.modalOpen = true;
             },
             close() { this.modalOpen = false; }
         }">
        <div class="page-stack section-gap">
            <section class="card table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <div class="panel-title">Saved Addresses</div>
                        <p class="panel-copy">Shipping and billing addresses saved by this customer.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" @click="open(null)">+ Add Address</button>
                </div>

                @if ($addresses->isEmpty())
                    <div class="empty-state" style="padding:32px 0;">
                        <div class="empty-state-title">No addresses</div>
                        <p class="empty-state-copy">This customer has not saved any addresses yet.</p>
                    </div>
                @else
                    <div class="form-grid form-grid-2" style="padding:16px;">
                        @foreach ($addresses as $addr)
                            <div class="card" style="position:relative; padding:16px;">
                                @if ($addr->is_default)
                                    <span class="badge badge-cyan" style="position:absolute; top:12px; right:12px; font-size:10px;">Default</span>
                                @endif
                                <div class="entity-title">{{ $addr->label ?: ($addr->full_name ?: 'Address #' . $addr->id) }}</div>
                                @if ($addr->full_name)
                                    <div class="entity-subtitle" style="margin-top:4px;">{{ $addr->full_name }}</div>
                                @endif
                                <div class="entity-subtitle" style="margin-top:4px;">{{ $addr->oneliner ?: '—' }}</div>
                                @if ($addr->phone)
                                    <div class="entity-subtitle">{{ $addr->phone }}</div>
                                @endif
                                @if ($addr->email)
                                    <div class="entity-subtitle">{{ $addr->email }}</div>
                                @endif
                                <div class="flex gap-2" style="margin-top:12px;">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        @click="open({{ json_encode([
                                            'id'        => $addr->id,
                                            'label'     => $addr->label ?? '',
                                            'fullName'  => $addr->full_name ?? '',
                                            'email'     => $addr->email ?? '',
                                            'phone'     => $addr->phone ?? '',
                                            'line1'     => $addr->address_line_1 ?? '',
                                            'city'      => $addr->city ?? '',
                                            'state'     => $addr->state ?? '',
                                            'country'   => $addr->country ?? '',
                                            'isDefault' => (bool) $addr->is_default,
                                        ]) }})">Edit</button>

                                    <form method="POST"
                                          action="{{ route('tenant.customers.addresses.destroy', [$customerId, $addr->id]) }}"
                                          onsubmit="return confirm('Delete this address?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Address modal ───────────────────────────────────────────────── --}}
        <template x-teleport="body">
            <div x-show="modalOpen" class="modal-backdrop" @click.self="close()" x-cloak>
                <div class="modal-dialog" style="max-width:560px">
                    <div class="modal-header">
                        <h3 x-text="editId ? 'Edit Address' : 'Add Address'"></h3>
                        <button type="button" @click="close()" class="modal-close">×</button>
                    </div>

                    <form method="POST"
                          :action="editId
                              ? '{{ route('tenant.customers.addresses.update', [$customerId, '__ID__']) }}'.replace('__ID__', editId)
                              : '{{ route('tenant.customers.addresses.store', $customerId) }}'"
                          class="page-stack">
                        @csrf
                        <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="field-label">Label <span class="field-optional">(e.g. Home, Work)</span></label>
                                <input class="field-control" type="text" name="addrLabel" :value="addr.label" placeholder="Home">
                            </div>
                            <div>
                                <label class="field-label">Recipient Name</label>
                                <input class="field-control" type="text" name="addrFullName" :value="addr.fullName" placeholder="Full name">
                            </div>
                            <div>
                                <label class="field-label">Email</label>
                                <input class="field-control" type="email" name="addrEmail" :value="addr.email" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="field-label">Phone</label>
                                <input class="field-control" type="tel" name="addrPhone" data-phone-input :value="addr.phone" placeholder="+1 555 000 0000">
                            </div>
                            <div class="form-grid-full">
                                <label class="field-label">Address Line 1 <span class="field-required">*</span></label>
                                <input class="field-control" type="text" name="addrLine1" :value="addr.line1" placeholder="Street address">
                            </div>
                            <div>
                                <label class="field-label">City</label>
                                <input class="field-control" type="text" name="addrCity" :value="addr.city" placeholder="City">
                            </div>
                            <div>
                                <label class="field-label">State / Region</label>
                                <input class="field-control" type="text" name="addrState" :value="addr.state" placeholder="State or region">
                            </div>
                            <div>
                                <label class="field-label">Country</label>
                                <input class="field-control" type="text" name="addrCountry" :value="addr.country" placeholder="Country">
                            </div>
                            <div style="display:flex; align-items:center; gap:10px; padding-top:4px;">
                                <label class="toggle-field" style="cursor:pointer; display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" name="addrIsDefault" value="1" :checked="addr.isDefault">
                                    <span class="field-label" style="margin:0;">Set as default address</span>
                                </label>
                            </div>
                        </div>

                        <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                            <button type="button" class="btn btn-secondary" @click="close()">Cancel</button>
                            <button type="submit" class="btn btn-primary" x-text="editId ? 'Update Address' : 'Add Address'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

</main>
@endsection
