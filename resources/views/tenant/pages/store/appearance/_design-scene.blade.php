{{-- Live mini storefront. Colors come from --pv-* variables set by appearance.js from the edition's own tokens
     (see "roles" in resources/theme-tokens/catalog.json); data-pv lists the roles an element's colors come from,
     so focusing a color field can highlight exactly what it changes. --}}
<aside class="t-pv-scene" aria-label="Live preview">
    <div class="t-pv-scene-head">
        <div>
            <strong>Live preview</strong>
            <span>Updates as you change colors and fonts</span>
        </div>
        <div class="t-pv-dir" role="group" aria-label="Preview direction">
            <button type="button" class="is-active" data-pv-dir="ltr">LTR</button>
            <button type="button" data-pv-dir="rtl">RTL</button>
        </div>
    </div>

    <div class="t-pv" data-pv-scene dir="ltr">
        <div class="t-pv-topbar" data-pv="topbar-bg topbar-text">
            <span data-pv-text="ltr:Free delivery on orders over $50|rtl:توصيل مجاني للطلبات فوق ٥٠ دولار">Free delivery on orders over $50</span>
            <span>EN · USD</span>
        </div>

        <div class="t-pv-header" data-pv="brand">
            <span class="t-pv-logo">store</span>
            <span class="t-pv-search" data-pv="surface text-muted" data-pv-text="ltr:Search products…|rtl:ابحث عن منتج…">Search products…</span>
            <span class="t-pv-cart" data-pv="brand-alt">Cart 2</span>
        </div>

        <div class="t-pv-nav" data-pv="text surface border">
            <span data-pv-text="ltr:New in|rtl:وصل حديثًا">New in</span>
            <span data-pv-text="ltr:Best sellers|rtl:الأكثر مبيعًا">Best sellers</span>
            <span data-pv-text="ltr:Offers|rtl:العروض">Offers</span>
        </div>

        <div class="t-pv-hero" data-pv="page-bg hero-title hero-sub">
            <div class="t-pv-hero-copy">
                <b data-pv="hero-title text" data-pv-text="ltr:Summer collection|rtl:مجموعة الصيف">Summer collection</b>
                <small data-pv="hero-sub text-muted" data-pv-text="ltr:Up to 40% off selected items|rtl:خصم يصل إلى ٤٠٪ على منتجات مختارة">Up to 40% off selected items</small>
                <span class="t-pv-btn" data-pv="brand" data-pv-text="ltr:Shop now|rtl:تسوّق الآن">Shop now</span>
            </div>
            <div class="t-pv-hero-art" data-pv="brand-alt"></div>
        </div>

        <div class="t-pv-cards" data-pv="page-bg">
            @foreach([['ltr:Wireless headphones|rtl:سماعات لاسلكية', '$49.00', '$69.00', '-30%'], ['ltr:Leather backpack|rtl:حقيبة جلدية', '$89.00', '$99.00', '-10%']] as [$name, $price, $old, $off])
                <div class="t-pv-card" data-pv="surface border">
                    <div class="t-pv-card-img" data-pv="page-bg"></div>
                    <b data-pv="text" data-pv-text="{{ $name }}">{{ explode('|', $name)[0] === $name ? $name : substr(explode('|', $name)[0], 4) }}</b>
                    <small data-pv="text-muted" data-pv-text="ltr:In stock · delivery tomorrow|rtl:متوفر · توصيل غدًا">In stock · delivery tomorrow</small>
                    <div class="t-pv-price-row">
                        <span class="t-pv-price" data-pv="price brand">{{ $price }}</span>
                        <s data-pv="text-muted">{{ $old }}</s>
                        <em class="t-pv-chip-off" data-pv="discount">{{ $off }}</em>
                    </div>
                    <span class="t-pv-btn t-pv-btn-sm" data-pv="brand" data-pv-text="ltr:Add to cart|rtl:أضف للسلة">Add to cart</span>
                </div>
            @endforeach
        </div>

        <div class="t-pv-flash" data-pv="flash-bg flash-accent">
            <b data-pv-text="ltr:Flash sale|rtl:تخفيضات سريعة">Flash sale</b>
            <span class="t-pv-timer" data-pv="flash-accent"><i>02</i><i>14</i><i>37</i></span>
        </div>

        <div class="t-pv-status" data-pv="page-bg">
            <span data-pv="success" data-pv-text="ltr:✓ In stock|rtl:✓ متوفر">✓ In stock</span>
            <span data-pv="error" data-pv-text="ltr:✕ Out of stock|rtl:✕ نفدت الكمية">✕ Out of stock</span>
        </div>

        <div class="t-pv-footer" data-pv="footer-bg footer-text">
            <div class="t-pv-footer-cols">
                <span data-pv-text="ltr:Help|rtl:المساعدة">Help</span><span data-pv-text="ltr:Shipping|rtl:الشحن">Shipping</span><span data-pv-text="ltr:Returns|rtl:الإرجاع">Returns</span>
            </div>
            <div class="t-pv-footer-bar" data-pv="footer-bar footer-text">© 2026 store</div>
        </div>
    </div>
    <p class="t-pv-note">Simplified illustration of this edition's layout. Hover or focus a color to see what it changes.</p>
</aside>
