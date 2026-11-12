@extends('tenant.layouts.app')

@section('title', $customer->full_name ?: __('Customer Detail'))

@php
    $orders = $detail['orders'] ?? [];
    $addresses = $detail['addresses'] ?? collect();
@endphp

@section('content')
    <x-tenant::page-header
        title="{{ $customer->full_name ?: __('Customer Detail') }}"
        badge="CRM"
        :description="__('Full customer profile — orders, payment history, saved addresses and inline editing.')"
        back="{{ route('tenant.customers.index') }}">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">{{ __('← Customers') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::tabs
        :tabs="[
            'profile' => __('Profile'),
            'orders' => __('Orders (:count)', ['count' => $detail['orderCount'] ?? 0]),
            'payments' => __('Payments'),
            'addresses' => __('Addresses (:count)', ['count' => $addresses->count()]),
        ]"
        :active="$activeTab"
        mode="hash">

        {{-- ═══ TAB: PROFILE ═══ --}}
        <x-tenant::tab-panel key="profile" :active="$activeTab === 'profile'">
            <x-tenant::card-collapse :title="__('Profile Details')" :subtitle="__('Update customer name, contact info, status and password.')" :open="true">
                <x-tenant::form
                    method="PUT"
                    action="{{ route('tenant.customers.update', $customerId) }}"
                    validate="{{ route('tenant.customers.validate.update', $customerId) }}"
                    success="none">
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="full_name" :label="__('Full Name')" required value="{{ $customer->full_name }}" :placeholder="__('Full name')" />
                        <x-tenant::input type="email" name="email" :label="__('Email')" required value="{{ $customer->email }}" placeholder="customer@example.com" />
                        <x-tenant::phone name="phone" :label="__('Phone')" value="{{ $phone }}" />
                        <x-tenant::select2 name="country_id" :label="__('Country')" :options="$countries" :value="$customer->country_id" :placeholder="__('— select country —')" />
                        <x-tenant::select2 name="city_id" :label="__('City')" :value="$customer->city_id"
                            :selected="$customer->city_id && $cities->has($customer->city_id) ? [$customer->city_id => $cities->get($customer->city_id)] : []"
                            ajax-url="{{ route('tenant.cities.by-country') }}?format=select2"
                            depends-on="country_id" min-input="0" :placeholder="__('— select city —')" />
                        <div class="form-grid-full">
                            <x-tenant::input name="address" :label="__('Default Address')" value="{{ $customer->address }}" :placeholder="__('Short freeform address')" />
                        </div>
                        <x-tenant::input type="password" toggle name="password" :label="__('New Password')" :placeholder="__('Min 8 characters')" autocomplete="new-password" />
                        <div>
                            <x-tenant::input type="password" toggle name="password_confirmation" :label="__('Confirm Password')" :placeholder="__('Repeat password')" autocomplete="new-password" />
                            <button type="button" class="btn btn-secondary btn-sm" data-generate-password style="margin-top:6px;">{{ __('Generate') }}</button>
                        </div>
                        <x-tenant::switch name="active" :checked="$customer->active" :label="__('Active account')" />
                    </div>

                    <div class="page-actions compact-actions justify-end">
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
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
                            <div class="empty-state-title">{{ __('No orders yet') }}</div>
                            <p class="empty-state-copy">{{ __('This customer has not placed any orders.') }}</p>
                        </div>
                    </div>
                @else
                    @foreach ($orders as $order)
                        @php
                            $fin = $order['financials'] ?? [];
                            $status = $order['status'];
                            $statusLabel = $status instanceof \App\Enums\OrderStatus ? __($status->label()) : (string) $status;
                        @endphp
                        <x-tenant::card-collapse
                            title="#{{ $order['uuid'] ?? $order['id'] }}"
                            subtitle="{{ $statusLabel }} · {{ $order['paid'] ? __('Paid') : __('Unpaid') }} · ${{ number_format((float) ($fin['grand_total'] ?? 0), 2) }}"
                            :open="false">

                            <div class="details-grid">
                                <section class="details-panel">
                                    <div class="details-header"><h4 class="panel-title">{{ __('Order Info') }}</h4></div>
                                    <div class="details-list">
                                        <div class="details-kv">
                                            <span class="details-label">{{ __('Status') }}</span>
                                            <span class="details-value"><x-tenant::status-badge :status="$status" /></span>
                                        </div>
                                        <div class="details-kv">
                                            <span class="details-label">{{ __('Payment') }}</span>
                                            <span class="details-value"><x-tenant::status-badge :status="$order['paid'] ? 'paid' : 'pending'" /></span>
                                        </div>
                                        <div class="details-kv"><span class="details-label">{{ __('Gateway') }}</span><span class="details-value">{{ $order['gateway'] ?? '-' }}</span></div>
                                        <div class="details-kv"><span class="details-label">{{ __('Placed At') }}</span><span class="details-value">{{ optional($order['created_at'])->format('M d, Y H:i') ?? '-' }}</span></div>
                                        <div class="details-kv">
                                            <span class="details-label">
                                                <a href="{{ route('tenant.orders.show', $order['id']) }}" class="btn btn-secondary btn-sm" style="margin-top:6px;">{{ __('View Full Order →') }}</a>
                                            </span>
                                        </div>
                                    </div>
                                </section>

                                <section class="details-panel">
                                    <div class="details-header"><h4 class="panel-title">{{ __('Financials') }}</h4></div>
                                    <div class="details-list">
                                        <div class="details-kv"><span class="details-label">{{ __('Items Grand Total') }}</span><span class="details-value">${{ number_format((float) ($fin['items_grand_total'] ?? 0), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">{{ __('Shipping') }}</span><span class="details-value">+${{ number_format((float) ($fin['shipping_charge'] ?? 0), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">{{ __('Coupon') }}</span><span class="details-value">−${{ number_format((float) ($fin['coupon_discount'] ?? 0), 2) }}</span></div>
                                        <div class="details-kv"><span class="details-label">{{ __('Grand Total') }}</span><span class="details-value" style="font-weight:600;">${{ number_format((float) ($fin['grand_total'] ?? 0), 2) }}</span></div>
                                    </div>
                                </section>

                                <section class="details-panel full">
                                    <div class="details-header"><h4 class="panel-title">{{ __('Line Items') }}</h4></div>
                                    <x-tenant::table :headers="[__('Product'), __('Variant'), __('Qty'), __('Price')]">
                                        @forelse ($order['items'] as $item)
                                            <tr>
                                                <td><div class="details-inline-title">{{ $item['name'] }}</div></td>
                                                <td>{{ $item['variant'] ?? '—' }}</td>
                                                <td>{{ $item['qty'] }}</td>
                                                <td>${{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><div class="json-empty">{{ __('No items.') }}</div></td></tr>
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
                :title="__('Payment History')"
                :description="__('All orders with payment status and amounts for this customer.')" />
        </x-tenant::tab-panel>

        {{-- ═══ TAB: ADDRESSES ═══ --}}
        <x-tenant::tab-panel key="addresses" :active="$activeTab === 'addresses'">
            <section class="card table-card-shell ds-table">
                <div class="table-header-shell">
                    <div>
                        <div class="panel-title">{{ __('Saved Addresses') }}</div>
                        <p class="panel-copy">{{ __('Shipping and billing addresses saved by this customer.') }}</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-modal-open="address-modal">{{ __('+ Add Address') }}</button>
                </div>

                @if ($addresses->isEmpty())
                    <div class="empty-state" style="padding:32px 0;">
                        <div class="empty-state-title">{{ __('No addresses') }}</div>
                        <p class="empty-state-copy">{{ __('This customer has not saved any addresses yet.') }}</p>
                    </div>
                @else
                    <div class="form-grid form-grid-2" style="padding:16px;">
                        @foreach ($addresses as $addr)
                            <div class="card" style="position:relative; padding:16px;">
                                @if ($addr->is_default)
                                    <span class="badge badge-cyan" style="position:absolute; top:12px; right:12px; font-size:10px;">{{ __('Default') }}</span>
                                @endif
                                <div class="entity-title">{{ $addr->label ?: ($addr->full_name ?: __('Address #:id', ['id' => $addr->id])) }}</div>
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
                                        data-modal-method="PUT">{{ __('Edit') }}</button>

                                    <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                        data-action-url="{{ route('tenant.customers.addresses.destroy', [$customerId, $addr->id]) }}"
                                        data-action-method="DELETE"
                                        data-confirm="{{ __('Delete this address?') }}"
                                        data-success="reload-page">{{ __('Delete') }}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </x-tenant::tab-panel>
    </x-tenant::tabs>

    {{-- ── Address modal ─────────────────────────────────────────────────── --}}
    <x-tenant::modal id="address-modal" :title="__('Add / Edit Address')" size="lg">
        <x-tenant::form
            action="{{ route('tenant.customers.addresses.store', $customerId) }}"
            method="POST"
            validate="{{ route('tenant.customers.addresses.validate', $customerId) }}"
            success="close-modal reload-page">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="label" :label="__('Label')" :help="__('e.g. Home, Work')" :placeholder="__('Home')" />
                <x-tenant::input name="full_name" :label="__('Recipient Name')" :placeholder="__('Full name')" />
                <x-tenant::input type="email" name="email" :label="__('Email')" placeholder="email@example.com" />
                <x-tenant::phone name="phone" :label="__('Phone')" />
                <div class="form-grid-full">
                    <x-tenant::input name="address_line_1" :label="__('Address Line 1')" required :placeholder="__('Street address')" />
                </div>
                <x-tenant::input name="city" :label="__('City')" :placeholder="__('City')" />
                <x-tenant::input name="state" :label="__('State / Region')" :placeholder="__('State or region')" />
                <x-tenant::input name="country" :label="__('Country')" :placeholder="__('Country')" />
                <x-tenant::switch name="is_default" :label="__('Set as default address')" />
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Address') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customer-detail.js')
@endpush
