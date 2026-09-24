{{-- Dashboard "Did you find a product and want it in your store?" banner → product request form. --}}

@php
    $check = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="6.67"/><path d="M5.33 8.33l1.67 1.67 3.67-3.67"/></svg>';
@endphp

<section class="db-section db-request fu d2">
    <div class="db-request-copy">
        <div>
            <h2 class="db-brand-title">Did you find a product and want it in your store?</h2>
            <p class="db-brand-text">Just send the name or link. We'll check the source, price, and shipping, then add it to your catalog when it's suitable.</p>
        </div>

        <ul class="db-hero-points">
            <li>{!! $check !!} clear follow-up</li>
            <li>{!! $check !!} Cost calculation</li>
            <li>{!! $check !!} Source review</li>
        </ul>

        <a href="{{ route('tenant.product-requests.create') }}" class="btn btn-primary btn-lg db-request-cta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v8M16 12H8"/><path d="M2.5 12c0-4.48 0-6.72 1.39-8.11C5.28 2.5 7.52 2.5 12 2.5s6.72 0 8.11 1.39C21.5 5.28 21.5 7.52 21.5 12s0 6.72-1.39 8.11C18.72 21.5 16.48 21.5 12 21.5s-6.72 0-8.11-1.39C2.5 18.72 2.5 16.48 2.5 12z"/></svg>
            Order a product now
        </a>
    </div>

    <div class="db-request-art" aria-hidden="true">
        {{-- How a request moves: sent → in progress → added --}}
        <ol class="db-request-steps">
            <li class="is-done">{!! $check !!} Order product</li>
            <li><i></i> In progress</li>
            <li><i></i> Added successfully</li>
        </ol>
        <img class="db-request-boxes" src="{{ asset('tenant-panel/product-request.png') }}"
            srcset="{{ asset('tenant-panel/product-request.png') }} 1x, {{ asset('tenant-panel/product-request@2x.png') }} 2x"
            alt="" width="186" height="186">
    </div>
</section>
