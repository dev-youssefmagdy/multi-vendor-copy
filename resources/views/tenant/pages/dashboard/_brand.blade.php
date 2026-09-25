{{--
    Dashboard "Ready to turn some of your products into a genuine brand?" banner.
    The two options are a single-choice toggle (UI only); the CTA opens the
    existing brand-request form.
--}}

<section class="db-section db-brand fu d2">
    <div class="db-brand-copy">
        <div>
            <h2 class="db-brand-title">Ready to turn some of your products into a genuine brand?</h2>
            <p class="db-brand-text">All your shipments automatically carry your store's identity.<br>And if you want to take it a step further, we can create custom products featuring your brand on both the product itself and its packaging.</p>
        </div>

        <div class="db-brand-choose">
            <h3>Select one of blew and start your brand</h3>
            <div class="db-brand-options" role="radiogroup" aria-label="Brand availability" data-brand-options>
                <button type="button" class="btn-tile" role="radio" aria-checked="false" data-brand-option="exclusive">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16.5v-2"/><path d="M4.27 18.84c.23 1.67 1.61 2.98 3.3 3.06 1.42.07 2.87.1 4.43.1s3.01-.03 4.43-.1c1.69-.08 3.07-1.39 3.3-3.06.14-1.02.27-2.06.27-3.12s-.13-2.1-.27-3.12c-.23-1.67-1.61-2.98-3.3-3.06C15.01 8.47 13.56 8.44 12 8.44s-3.01.03-4.43.1c-1.69.08-3.07 1.39-3.3 3.06-.14 1.02-.27 2.06-.27 3.12s.13 2.1.27 3.12z"/><path d="M7.5 8.5V6.25a4.5 4.5 0 0 1 9 0V8.5"/></svg>
                    Exclusive to your store
                </button>
                <button type="button" class="btn-tile" role="radio" aria-checked="false" data-brand-option="network">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.75"/><path d="M8 12c0 5.25 1.8 9.75 4 9.75s4-4.5 4-9.75S14.2 2.25 12 2.25 8 6.75 8 12z"/><path d="M3 9h18M3 15h18"/></svg>
                    <span class="db-title-full">available through the merchant network</span><span class="db-title-short">available merchant network</span>
                </button>
            </div>
        </div>

        <a href="{{ route('tenant.brand-requests.create') }}" class="btn btn-primary btn-lg db-brand-cta">
            Start building your Brand
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m16 0l-6 6m6-6l-6-6"/></svg>
        </a>
    </div>

    <div class="db-brand-art" aria-hidden="true">
        <img class="db-brand-box" src="{{ asset('tenant-panel/brand-box.png') }}" alt="" width="256" height="193">

        <span class="db-brand-step is-1"><b>1.</b> Choose the product</span>
        <span class="db-brand-step is-2"><b>2.</b> Personalize the identity</span>
        <span class="db-brand-step is-3"><b>3.</b> We produce and package</span>
    </div>
</section>
