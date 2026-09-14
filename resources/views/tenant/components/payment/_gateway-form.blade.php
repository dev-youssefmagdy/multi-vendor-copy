<x-tenant::form
    :id="$id.'-form'"
    :action="$action"
    :method="$method"
    :validate="$validate"
    success="redirect"
    data-tenant-payment-modal
    data-modal-id="{{ $id }}"
>
    @if($summarySlot)
        {!! $summarySlot !!}
    @endif

    <x-tenant::radio-group name="gateway" label="Payment Gateway" variant="cards" :columns="1" :options="$gatewayOptions" :value="$selectedCode" data-payment-gateway-select />
    <div data-payment-ready hidden></div>

    @foreach($presented['gateways'] as $gateway)
        @continue(!$gateway['is_inline'])
        <div
            class="t-card-form"
            id="sp-inline-card-form-{{ $id }}-{{ $gateway['code'] }}"
            data-payment-inline-panel
            data-gateway="{{ $gateway['code'] }}"
            hidden
            @if($gateway['code'] === 'stripe')
                data-stripe-key="{{ $gateway['creds']['key'] ?? '' }}"
            @elseif($gateway['code'] === 'authorize_net')
                data-auth-login="{{ $gateway['creds']['login_id'] ?? '' }}"
                data-auth-client="{{ $gateway['creds']['client_key'] ?? ($gateway['creds']['transaction_key'] ?? '') }}"
                data-sandbox="{{ ($gateway['mode'] ?? 'live') === 'test' ? '1' : '0' }}"
            @elseif($gateway['code'] === '2checkout')
                data-2co-seller="{{ $gateway['creds']['seller_id'] ?? '' }}"
            @endif
        >
            <div class="t-card-form-badge">Secure card details</div>

            @if($gateway['code'] === 'stripe')
                <label class="field-label">Card</label>
                <div id="sp-stripe-card-element-{{ $id }}" class="t-stripe-card-element"></div>
                <p id="sp-stripe-card-errors-{{ $id }}" class="field-error" hidden></p>
                <input type="hidden" name="stripe_token" data-token-field="stripe_token">
            @else
                <div class="form-grid form-grid-1">
                    <x-tenant::input type="text" id="sp-card-number-{{ $id }}" label="Card number" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" />
                    <div class="form-grid form-grid-2">
                        <x-tenant::input type="text" id="sp-card-expiry-{{ $id }}" label="Expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM / YY" maxlength="7" />
                        <x-tenant::input type="text" id="sp-card-cvc-{{ $id }}" label="CVC" inputmode="numeric" autocomplete="cc-csc" placeholder="&bull;&bull;&bull;" maxlength="4" />
                    </div>
                </div>
                @if($gateway['code'] === 'authorize_net')
                    <input type="hidden" name="authnet_desc" data-token-field="authnet_desc">
                    <input type="hidden" name="authnet_value" data-token-field="authnet_value">
                @elseif($gateway['code'] === '2checkout')
                    <input type="hidden" name="twoco_token" data-token-field="twoco_token">
                @endif
                <p id="sp-card-errors-{{ $id }}" class="field-error" hidden></p>
            @endif
        </div>
    @endforeach

    @if($fieldsSlot)
        {!! $fieldsSlot !!}
    @endif

    <div class="page-actions compact-actions justify-end">
        @unless($inline)
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        @endunless
        <button type="submit" class="btn btn-primary" data-payment-submit>{{ $submitLabel }}</button>
    </div>
</x-tenant::form>
