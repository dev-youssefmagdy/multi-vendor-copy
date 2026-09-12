@extends('tenant.layouts.app')

@section('title', $customer->full_name ?: 'Customer Detail')

@php
    $orders = $detail['orders'] ?? [];
    $addresses = $detail['addresses'] ?? collect();
@endphp

@section('content')
    <x-tenant::page-header
        title="{{ $customer->full_name ?: 'Customer Detail' }}"
        badge="CRM"
        description="Full customer profile — orders, payment history, saved addresses and inline editing."
        back="{{ route('tenant.customers.index') }}">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">← Customers</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::tabs
        :tabs="[
            'profile' => 'Profile',
            'orders' => 'Orders (' . ($detail['orderCount'] ?? 0) . ')',
            'payments' => 'Payments',
            'addresses' => 'Addresses (' . $addresses->count() . ')',
        ]"
        :active="$activeTab"
        mode="hash">

        {{-- ═══ TAB: PROFILE ═══ --}}
        <x-tenant::tab-panel key="profile" :active="$activeTab === 'profile'">
            <x-tenant::card-collapse title="Profile Details" subtitle="Update customer name, contact info, status and password." :open="true">
                <x-tenant::form
                    method="PUT"
                    action="{{ route('tenant.customers.update', $customerId) }}"
                    validate="{{ route('tenant.customers.validate.update', $customerId) }}"
                    success="none">
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="full_name" label="Full Name" required value="{{ $customer->full_name }}" placeholder="Full name" />
                        <x-tenant::input type="email" name="email" label="Email" required value="{{ $customer->email }}" placeholder="customer@example.com" />
                        <x-tenant::phone name="phone" label="Phone" value="{{ $phone }}" />
                        <x-tenant::select2 name="country_id" label="Country" :options="$countries" :value="$customer->country_id" placeholder="— select country —" />
                        <x-tenant::select2 name="city_id" label="City" :value="$customer->city_id"
                            :selected="$customer->city_id && $cities->has($customer->city_id) ? [$customer->city_id => $cities->get($customer->city_id)] : []"
                            ajax-url="{{ route('tenant.cities.by-country') }}?format=select2"
                            depends-on="country_id" min-input="0" placeholder="— select city —" />
                        <div class="form-grid-full">
                            <x-tenant::input name="address" label="Default Address" value="{{ $customer->address }}" placeholder="Short freeform address" />
                        </div>
                        <x-tenant::input type="password" toggle name="password" label="New Password" placeholder="Min 8 characters" autocomplete="new-password" />
                        <div>
                            <x-tenant::input type="password" toggle name="password_confirmation" label="Confirm Password" placeholder="Repeat password" autocomplete="new-password" />
                            <button type="button" class="btn btn-secondary btn-sm" data-generate-password style="margin-top:6px;">Generate</button>
                        </div>
                        <x-tenant::switch name="active" :checked="$customer->active" label="Active account" />
                    </div>

                    <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </x-tenant::form>
            </x-tenant::card-collapse>
        </x-tenant::tab-panel>

        {{-- ═══ TAB: ORDERS ═══ --}}
        <x-tenant::tab-panel key="orders" :active="$activeTab === 'orders'">
            <div class="page-stack">
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
                            $fin = $order['financials'] ?? [];
                            $status = $order['status'];
                            $statusLabel = $status instanceof \App\Enums\OrderStatus ? $status->label() : (string) $status;
                        @endphp
                        <x-tenant::card-collapse
                            title="#{{ $order['uuid'] ?? $order['id'] }}"
                            subtitle="{{ $statusLabel }} · {{ $order['paid'] ? 'Paid' : 'Unpaid' }} · ${{ number_format((float) ($fin['grand_total'] ?? 0), 2) }}"
                            :open="false">

                            <div class="details-grid">
                                <section class="details-panel">
                                    <div class="details-header"><h4 class="panel-title">Order Info</h4></div>
                                    <div class="details-list">
                                        <div class="details-kv">
                                            <span class="details-label">Status</span>
                                            <span class="details-value"><x-tenant::status-badge :status="$status" /></span>
                                        </div>
                                        <div class="details-kv">
                                            <span class="details-label">Payment</span>
                                            <span class="details-value"><x-tenant::status-badge :status="$order['paid'] ? 'paid' : 'pending'" /></span>
                                        </div>
                                        <div class="details-kv"><span class="details-label">Gateway</span><span class="details-value">{{ $order['gateway'] ?? '-' }}</span></div>
                                        <div class="details-kv"><span class="details-label">Placed At</span><span class="details-value">{{ optional($order['created_at'])->format('M d, Y H:i') ?? '-' }}</span></div>
                                        <div class="details-kv">
                                            <span class="details-label">
                                                <a href="{{ route('tenant.orders.show', $order['id']) }}" class="btn btn-secondary btn-sm" style="margin-top:6px;">View Full Order →</a>
                                            </span>
                                        </div>
                                    </div>
                                </section>

                                <section class="details-panel">
                                    <div class="details-header"><h4 class="panel-title">Financials</h4></div>
                                    <div class="details-list">
                                        <div class="details-kv"><span class="details-label">Subtotal</span><span class="details-value">${{ number_format((float) ($fin['subtotal'] ?? 0), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">Discount</span><span class="details-value">−${{ number_format((float) (($fin['items_discount'] ?? 0) + ($fin['discount'] ?? 0)), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">Tax</span><span class="details-value">+${{ number_format((float) (($fin['items_tax'] ?? 0) + ($fin['tax'] ?? 0)), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">Shipping</span><span class="details-value">+${{ number_format((float) ($fin['shipping_total'] ?? 0), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">Grand Total</span><span class="details-value" style="font-weight:600;">${{ number_format((float) ($fin['grand_total'] ?? 0), 2) }}</span></div>
                                    </div>
                                </section>

                                <section class="details-panel full">
                                    <div class="details-header"><h4 class="panel-title">Line Items</h4></div>
                                    <x-tenant::table :headers="['Product', 'Variant', 'Qty', 'Price']">
                                        @forelse ($order['items'] as $item)
                                            <tr>
                                                <td><div class="details-inline-title">{{ $item['name'] }}</div></td>
                                                <td>{{ $item['variant'] ?? '—' }}</td>
                                                <td>{{ $item['qty'] }}</td>
                                                <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><div class="json-empty">No items.</div></td></tr>
                                        @endforelse
                                    </x-tenant::table>
                                </section>
                            </div>
                        </x-tenant::card-collapse>
                    @endforeach
                @endif
            </div>
        </x-tenant::tab-panel>

        {{-- ═══ TAB: PAYMENTS ═══ --}}
        <x-tenant::tab-panel key="payments" :active="$activeTab === 'payments'">
            <x-tenant::datatable
                id="customer-payments-table"
                :url="route('tenant.customers.payments.data', $customerId)"
                :columns="$paymentsColumns"
                :order="[[5, 'desc']]"
                title="Payment History"
                description="All orders with payment status and amounts for this customer." />
        </x-tenant::tab-panel>

        {{-- ═══ TAB: ADDRESSES ═══ --}}
        <x-tenant::tab-panel key="addresses" :active="$activeTab === 'addresses'">
            <section class="card table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <div class="panel-title">Saved Addresses</div>
                        <p class="panel-copy">Shipping and billing addresses saved by this customer.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-modal-open="address-modal">+ Add Address</button>
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
                                        data-modal-open="address-modal"
                                        data-modal-fill-url="{{ route('tenant.customers.addresses.show', [$customerId, $addr->id]) }}"
                                        data-modal-action="{{ route('tenant.customers.addresses.update', [$customerId, $addr->id]) }}"
                                        data-modal-method="PUT">Edit</button>

                                    <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                        data-action-url="{{ route('tenant.customers.addresses.destroy', [$customerId, $addr->id]) }}"
                                        data-action-method="DELETE"
                                        data-confirm="Delete this address?"
                                        data-success="reload-page">Delete</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </x-tenant::tab-panel>
    </x-tenant::tabs>

    {{-- ── Address modal ─────────────────────────────────────────────────── --}}
    <x-tenant::modal id="address-modal" title="Add / Edit Address" size="lg">
        <x-tenant::form
            action="{{ route('tenant.customers.addresses.store', $customerId) }}"
            method="POST"
            validate="{{ route('tenant.customers.addresses.validate', $customerId) }}"
            success="close-modal reload-page">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="label" label="Label" help="e.g. Home, Work" placeholder="Home" />
                <x-tenant::input name="full_name" label="Recipient Name" placeholder="Full name" />
                <x-tenant::input type="email" name="email" label="Email" placeholder="email@example.com" />
                <x-tenant::phone name="phone" label="Phone" />
                <div class="form-grid-full">
                    <x-tenant::input name="address_line_1" label="Address Line 1" required placeholder="Street address" />
                </div>
                <x-tenant::input name="city" label="City" placeholder="City" />
                <x-tenant::input name="state" label="State / Region" placeholder="State or region" />
                <x-tenant::input name="country" label="Country" placeholder="Country" />
                <x-tenant::switch name="is_default" label="Set as default address" />
            </div>

            <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Address</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customer-detail.js')
@endpush
