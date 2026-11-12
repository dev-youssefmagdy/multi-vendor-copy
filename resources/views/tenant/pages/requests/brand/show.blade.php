@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$request->title" :badge="__($request->status->label())" :description="$description">
        <x-slot:actions>
            <a href="{{ route('tenant.brand-requests.index') }}" class="btn btn-secondary">{{ __('← Back to Requests') }}</a>
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
    <div class="card form-card fu d1 section-gap">
        <h3 class="panel-title">{{ __('Request Details') }}</h3>
        <dl style="display:grid;grid-template-columns:130px 1fr;gap:10px 16px;font-size:13px;">
            <dt class="entity-subtitle">{{ __('Status') }}</dt>
            <dd><span class="{{ $request->status->badgeClass() }}">{{ __($request->status->label()) }}</span></dd>

            <dt class="entity-subtitle">{{ __('Submitted') }}</dt>
            <dd class="entity-subtitle">{{ $request->created_at->format('M d, Y H:i') }}</dd>

            @if ($request->description)
                <dt class="entity-subtitle" style="padding-top:4px;">{{ __('Description') }}</dt>
                <dd class="entity-subtitle" style="white-space:pre-wrap;">{{ $request->description }}</dd>
            @endif

            @if ($request->admin_notes)
                <dt class="entity-subtitle" style="padding-top:4px;">{{ __('Admin Notes') }}</dt>
                <dd class="entity-subtitle" style="white-space:pre-wrap;background:var(--elevated);padding:8px 12px;border-radius:6px;">{{ $request->admin_notes }}</dd>
            @endif
        </dl>

        @if (!empty($request->attachments))
            <div style="margin-top:16px;">
                <p class="field-label" style="margin-bottom:8px;">{{ __('Attachments') }}</p>
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
        :title="__('Payment Requests')" :description="__('Review and pay outstanding invoices issued by the admin team.')"
        :empty-title="__('No payment requests')" :empty-copy="__('The admin hasn\'t issued any payment requests yet.')" />

    {{-- Chat --}}
    <section class="card form-card fu d3 section-gap" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="padding:24px;">
            <div>
                <h3 class="panel-title">{{ __('Messages') }}</h3>
                <p class="panel-copy">{{ __('Communicate with the admin team about this request.') }}</p>
            </div>
        </div>

        <x-tenant::chat id="brand-request-chat" :messages="$messages" :empty="__('No messages yet. Start a conversation with the admin team.')">
            <x-slot:composer>
                <x-tenant::form action="{{ route('tenant.brand-requests.messages', $request->id) }}"
                    :validate="route('tenant.brand-requests.messages.validate', $request->id)"
                    success="none" id="brand-chat-form">
                    <div style="display:flex;gap:10px;align-items:flex-end;">
                        <div style="flex:1;">
                            <x-tenant::textarea name="message" rows="2" :placeholder="__('Type a message to the admin…')" style="resize:none;" />
                        </div>
                        <button type="submit" class="btn btn-primary" style="align-self:flex-end;">{{ __('Send') }}</button>
                    </div>
                    <p class="entity-subtitle" style="margin-top:6px;">{{ __('Ctrl+Enter to send') }}</p>
                </x-tenant::form>
            </x-slot:composer>
        </x-tenant::chat>
    </section>

    {{-- Payment Modal --}}
    <x-tenant::payment.gateway-modal
        id="brand-pay-modal"
        :title="__('Pay Invoice')"
        :action="route('tenant.brand-requests.pay', $request->id)"
        :validate="route('tenant.brand-requests.pay.validate', $request->id)"
        :presented="$presented"
        :submit-label="__('Proceed to Payment')"
    >
        <x-slot:summary>
            <div class="locale-fields-group" data-brand-pay-summary style="margin-bottom:4px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;">
                    <div>
                        <div class="entity-title" data-brand-pay-summary-label>—</div>
                        <div class="entity-subtitle">{{ __('Brand Request #:id · :title', ['id' => $request->id, 'title' => $request->title]) }}</div>
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
