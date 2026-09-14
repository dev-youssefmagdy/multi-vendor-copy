@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$request->title" :badge="$request->status->label()" :description="$description">
        <x-slot:actions>
            <a href="{{ route('tenant.brand-requests.index') }}" class="btn btn-secondary">&larr; Back to Requests</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if (session('br_payment_success'))
        <div class="card section-gap notice-success">{{ session('br_payment_success') }}</div>
    @endif
    @if (session('br_payment_error'))
        <div class="card section-gap notice-error">{{ session('br_payment_error') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    {{-- Request Details --}}
    <div class="card fu d1 section-gap" style="padding:24px;">
        <h3 class="panel-title" style="margin-bottom:16px;">Request Details</h3>
        <dl style="display:grid;grid-template-columns:130px 1fr;gap:10px 16px;font-size:13px;">
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

        @if (!empty($request->attachments))
            <div style="margin-top:16px;">
                <p class="field-label" style="margin-bottom:8px;">Attachments</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach ($request->attachments as $path)
                        <a href="{{ asset('storage/' . $path) }}" target="_blank" class="badge badge-secondary">&#128206; {{ basename($path) }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Payment Requests --}}
    <x-tenant::datatable id="brand-payment-requests-table" mode="client" :paging="false" :searching="false"
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

        <x-tenant::chat id="brand-request-chat" :messages="$messages" empty="No messages yet. Start a conversation with the admin team.">
            <x-slot:composer>
                <x-tenant::form action="{{ route('tenant.brand-requests.messages', $request->id) }}"
                    :validate="route('tenant.brand-requests.messages.validate', $request->id)"
                    success="none" id="brand-chat-form">
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
        id="brand-pay-modal"
        title="Pay Invoice"
        :action="route('tenant.brand-requests.pay', $request->id)"
        :validate="route('tenant.brand-requests.pay.validate', $request->id)"
        :presented="$presented"
        submit-label="Proceed to Payment"
    >
        <x-slot:summary>
            <div class="locale-fields-group" data-brand-pay-summary style="margin-bottom:4px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;">
                    <div>
                        <div class="entity-title" data-brand-pay-summary-label>—</div>
                        <div class="entity-subtitle">Brand Request #{{ $request->id }} &middot; {{ $request->title }}</div>
                    </div>
                    <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;" data-brand-pay-summary-amount>—</div>
                </div>
            </div>
        </x-slot:summary>
        <x-slot:fields>
            <input type="hidden" name="payment_request_id" value="" data-brand-pay-field>
        </x-slot:fields>
    </x-tenant::payment.gateway-modal>

    <script type="application/json" id="brand-request-context">
        {!! json_encode(['tenantId' => tenant('id'), 'requestId' => $request->id]) !!}
    </script>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/brand-show.js')
@endpush
