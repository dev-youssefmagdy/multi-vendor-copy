{{-- Pay-central panel on the order detail page — same breakdown and gateway flow as Finance > Vendor Purchases > Pay Central. --}}
<section class="details-panel full" id="pay-central">
    <div class="details-header">
        <h4 class="panel-title">Pay Central</h4>
        <p class="panel-copy">You collected payment for this order. Select a payment gateway and settle the product and shipping cost with central.</p>
    </div>

    <div class="vso-layout">
        <div data-breakdown-body>
            @include('tenant.pages.finance.vendor-settle._breakdown', ['breakdown' => $settlement['breakdown'], 'selected' => $settlement['presented']['selected']])
        </div>

        <div data-vendor-settle data-breakdown-url="{{ route('tenant.finance.vendor-purchase-settle.breakdown', ['orderId' => $orderId]) }}">
            <x-tenant::payment.gateway-modal
                id="vendor-settle-{{ $orderId }}"
                title="Pay Central"
                :action="route('tenant.finance.vendor-purchase-settle.pay', ['orderId' => $orderId])"
                :validate="route('tenant.finance.vendor-purchase-settle.pay.validate', ['orderId' => $orderId])"
                :presented="$settlement['presented']"
                submit-label="Proceed to Payment"
                :inline="true"
            />
        </div>
    </div>
</section>
