@extends('tenant.layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-tenant::page-header :title="$pageTitle" :badge="$badge" :description="$pageDescription">
        <x-slot:actions>
            <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-secondary">&larr; Back to Requests</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if (session('mf_payment_success'))
        <div class="card section-gap notice-success">{{ session('mf_payment_success') }}</div>
    @endif
    @if (session('mf_payment_error'))
        <div class="card section-gap notice-error">{{ session('mf_payment_error') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    {{-- Request Details --}}
    <div class="card fu d1 section-gap" style="padding:24px;">
        <h3 class="panel-title" style="margin-bottom:16px;">Request Details</h3>
        <dl style="display:grid;grid-template-columns:130px 1fr;gap:10px 16px;font-size:13px;">
            <dt class="entity-subtitle">Product</dt>
            <dd class="entity-title">{{ $request->product_name }}</dd>

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
                <dd class="entity-subtitle" style="white-space:pre-wrap;background:var(--elevated);padding:8px 12px;border-radius:6px;">{{ $request->admin_notes }}</dd>
            @endif
        </dl>
    </div>

    {{-- Payment Requests --}}
    @php
        $paymentColumns = [
            \App\Support\Tenant\TableColumn::make('id', '#'),
            \App\Support\Tenant\TableColumn::make('label', 'Label'),
            \App\Support\Tenant\TableColumn::make('amount', 'Amount'),
            \App\Support\Tenant\TableColumn::make('status', 'Status'),
            \App\Support\Tenant\TableColumn::make('notes', 'Notes'),
            \App\Support\Tenant\TableColumn::actions(),
        ];

        $paymentRows = $paymentRequests->map(function ($pr) {
            $amountCell = '<div class="entity-title">' . e($pr->currency) . ' ' . number_format((float) $pr->amount, 2) . '</div>';
            if ($pr->paid_at) {
                $amountCell .= '<div class="entity-subtitle">Paid ' . $pr->paid_at->format('M d, Y') . ' via ' . e(strtoupper($pr->gateway_code ?? '')) . '</div>';
            }

            $actionCell = '—';
            if ($pr->status->value === 'pending') {
                $actionCell = '<button type="button" class="btn btn-primary btn-sm" data-mf-pay-request '
                    . 'data-payment-request-id="' . $pr->id . '" '
                    . 'data-payment-request-label="' . e($pr->label) . '" '
                    . 'data-payment-request-currency="' . e($pr->currency) . '" '
                    . 'data-payment-request-amount="' . number_format((float) $pr->amount, 2) . '">Pay Now</button>';
            }

            return [
                '#' . $pr->id,
                e($pr->label),
                $amountCell,
                '<span class="' . e($pr->status->badgeClass()) . '">' . e($pr->status->label()) . '</span>',
                $pr->notes ? e($pr->notes) : '<span class="entity-subtitle">—</span>',
                $actionCell,
            ];
        })->all();
    @endphp

    <x-tenant::datatable id="manufacturing-payment-requests-table" mode="client" :paging="false" :searching="false"
        :columns="$paymentColumns" :rows="$paymentRows"
        title="Payment Requests" description="Review and pay outstanding invoices issued by the admin team."
        empty-title="No payment requests" empty-copy="The admin hasn't issued any payment requests yet." />

    {{-- Chat --}}
    <section class="card fu d3 section-gap" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="padding:16px 20px;">
            <div>
                <h3 class="panel-title">Messages</h3>
                <p class="panel-copy">Communicate with the admin team about this request.</p>
            </div>
        </div>

        <x-tenant::chat id="manufacturing-request-chat" :messages="$messages" empty="No messages yet. Start a conversation with the admin team.">
            <x-slot:composer>
                <x-tenant::form action="{{ route('tenant.manufacturing.messages', $request->id) }}"
                    :validate="route('tenant.manufacturing.messages.validate', $request->id)"
                    success="none" id="mf-chat-form">
                    <div style="display:flex;gap:10px;align-items:flex-end;">
                        <div style="flex:1;">
                            <x-tenant::textarea name="message" rows="2" placeholder="Type a message to the admin…" style="resize:none;" />
                        </div>
                        <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Send</button>
                    </div>
                    <p class="entity-subtitle" style="margin-top:6px;">Ctrl+Enter to send</p>
                </x-tenant::form>
            </x-slot:composer>
        </x-tenant::chat>
    </section>

    {{-- Payment Modal --}}
    <x-tenant::payment.gateway-modal
        id="mf-pay-modal"
        title="Pay Invoice"
        :action="route('tenant.manufacturing.pay', $request->id)"
        :validate="route('tenant.manufacturing.pay.validate', $request->id)"
        :presented="$presented"
        submit-label="Proceed to Payment"
    >
        <x-slot:summary>
            <div class="locale-fields-group" data-mf-pay-summary style="margin-bottom:4px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;">
                    <div>
                        <div class="entity-title" data-mf-pay-summary-label>—</div>
                        <div class="entity-subtitle">Manufacturing Request #{{ $request->id }} &middot; {{ $request->product_name }}</div>
                    </div>
                    <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;" data-mf-pay-summary-amount>—</div>
                </div>
            </div>
        </x-slot:summary>
        <x-slot:fields>
            <input type="hidden" name="payment_request_id" value="" data-mf-pay-field>
        </x-slot:fields>
    </x-tenant::payment.gateway-modal>

    <script type="application/json" id="manufacturing-request-context">
        {!! json_encode(['tenantId' => tenant('id'), 'requestId' => $request->id]) !!}
    </script>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-show.js')
@endpush
