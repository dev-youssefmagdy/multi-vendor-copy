@php
    // Reusable shapes, referenced by multiple pages below so nothing has to be redefined.

    $categoryShape = [
        ['key' => 'id', 'type' => 'int', 'desc' => 'e.g. 12'],
        ['key' => 'slug', 'type' => 'string', 'desc' => 'e.g. "electronics"'],
        ['key' => 'name', 'type' => 'string', 'desc' => "via ->translationValue('name')"],
        ['key' => 'image_url', 'type' => 'string|null'],
        ['key' => 'children', 'type' => 'Collection<Category>', 'desc' => 'same shape, one level deep'],
    ];

    $currencyShape = [
        ['key' => 'code', 'type' => 'string', 'desc' => 'e.g. "USD"'],
        ['key' => 'symbol', 'type' => 'string', 'desc' => 'e.g. "$"'],
        ['key' => 'conversion_rate', 'type' => 'float', 'desc' => 'e.g. 1.0'],
        ['key' => 'is_default', 'type' => 'bool'],
    ];

    $socialLinkShape = [
        ['key' => 'url', 'type' => 'string', 'desc' => 'e.g. https://instagram.com/...'],
        ['key' => 'icon', 'type' => 'string', 'desc' => 'facebook | twitter | instagram | linkedin | youtube | whatsapp'],
    ];

    $pricingShape = [
        ['key' => 'current_price', 'type' => 'float', 'desc' => 'e.g. 29.99'],
        ['key' => 'original_price', 'type' => 'float', 'desc' => 'e.g. 39.99'],
        ['key' => 'has_discount', 'type' => 'bool'],
        ['key' => 'discount_percentage', 'type' => 'float', 'desc' => 'e.g. 25.0'],
    ];

    $productShape = [
        ['key' => 'id', 'type' => 'int', 'desc' => 'e.g. 501'],
        ['key' => 'slug', 'type' => 'string', 'desc' => 'e.g. "blue-t-shirt"'],
        ['key' => 'name', 'type' => 'string', 'desc' => "via ->translationValue('name')"],
        ['key' => 'primary_image_url', 'type' => 'string|null', 'desc' => 'accessor'],
        ['key' => 'storefrontPricing()', 'type' => 'method', 'children' => $pricingShape],
    ];

    $cartItemShape = [
        ['key' => 'product_id', 'type' => 'int', 'desc' => 'e.g. 501'],
        ['key' => 'variant_id', 'type' => 'int', 'desc' => 'e.g. 12'],
        ['key' => 'qty', 'type' => 'int', 'desc' => 'e.g. 2'],
        ['key' => 'product', 'type' => 'Model', 'desc' => 'Product model (name, image, slug)'],
        ['key' => 'price', 'type' => 'float', 'desc' => 'e.g. 29.99'],
        ['key' => 'subtotal', 'type' => 'float', 'desc' => 'e.g. 59.98'],
    ];

    // Shared layout variables — injected on every storefront page via the platform's view composer.
    $sharedVars = [
        ['key' => 'storeName', 'type' => 'string'],
        ['key' => 'logoPath', 'type' => 'string|null'],
        ['key' => 'cartCount', 'type' => 'int'],
        ['key' => 'rootCategories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'categories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'socialLinks', 'type' => 'Collection<SocialLink>', 'children' => $socialLinkShape],
        ['key' => 'footerText', 'type' => 'string'],
        ['key' => 'footerCopyright', 'type' => 'string'],
        ['key' => 'languages', 'type' => 'Collection<Language>'],
        ['key' => 'currencies', 'type' => 'Collection<Currency>', 'children' => $currencyShape],
        ['key' => 'currentLanguage', 'type' => 'Language|null'],
        ['key' => 'currentCurrency', 'type' => 'Currency|null', 'children' => $currencyShape],
        ['key' => 'hasFreeShipping', 'type' => 'bool'],
        ['key' => 'customerCountryId', 'type' => 'int|null'],
    ];

    // home (pages/home/index.blade.php)
    $bannerShape = [
        ['key' => 'image_path', 'type' => 'string', 'desc' => 'pass through tenant_asset()'],
        ['key' => 'url', 'type' => 'string|null'],
        ['key' => 'title', 'type' => 'string', 'desc' => "via ->translationValue('title')"],
        ['key' => 'subtitle', 'type' => 'string', 'desc' => "via ->translationValue('subtitle')"],
        ['key' => 'button_text', 'type' => 'string', 'desc' => "via ->translationValue('button_text')"],
    ];
    $flashSaleShape = [
        ['key' => 'discount_percentage', 'type' => 'float', 'desc' => 'e.g. 25.0'],
        ['key' => 'end_date', 'type' => 'Carbon|null', 'desc' => "call ->toIso8601String()"],
        ['key' => 'bannerImageUrl()', 'type' => 'method', 'desc' => 'returns string|null'],
        ['key' => 'products', 'type' => 'Collection<Product>', 'children' => $productShape],
    ];
    $homeVars = [
        ['key' => 'banners', 'type' => 'Collection<Banner>', 'children' => $bannerShape],
        ['key' => 'flash_sales', 'type' => 'Collection<FlashSale>', 'children' => $flashSaleShape],
        ['key' => 'new_arrivals', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'recommended_products', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'best_sellers', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'trending_products', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'featured_products', 'type' => 'Collection<Product>', 'children' => $productShape],
    ];

    // product (pages/product.blade.php)
    $variantDataEntryShape = [
        ['key' => 'price', 'type' => 'float'],
        ['key' => 'stock', 'type' => 'int|null'],
        ['key' => 'image', 'type' => 'string|null'],
    ];
    $productVars = [
        ['key' => 'product', 'type' => 'Model', 'desc' => 'Product model (full)', 'children' => $productShape],
        ['key' => 'variants', 'type' => 'Collection<ProductVariant>'],
        ['key' => 'activeVariant', 'type' => 'ProductVariant|null'],
        ['key' => 'pricing', 'type' => 'object', 'desc' => 'same shape as storefrontPricing()', 'children' => $pricingShape],
        ['key' => 'sellPrice', 'type' => 'float'],
        ['key' => 'realPrice', 'type' => 'float|null'],
        ['key' => 'hasDiscount', 'type' => 'bool'],
        ['key' => 'discountPct', 'type' => 'float'],
        ['key' => 'displaySell', 'type' => 'string', 'desc' => 'formatted'],
        ['key' => 'displayReal', 'type' => 'string|null'],
        ['key' => 'savedAmount', 'type' => 'float'],
        ['key' => 'symbol', 'type' => 'string'],
        ['key' => 'rate', 'type' => 'float'],
        ['key' => 'isInStock', 'type' => 'bool'],
        ['key' => 'manageStock', 'type' => 'bool'],
        ['key' => 'stockValue', 'type' => 'int|null'],
        ['key' => 'weightGrams', 'type' => 'float'],
        ['key' => 'weightDisplay', 'type' => 'string'],
        ['key' => 'avgRating', 'type' => 'float'],
        ['key' => 'reviewCount', 'type' => 'int'],
        ['key' => 'ratingDistribution', 'type' => 'array<int,int>', 'desc' => 'star => count'],
        ['key' => 'latestReviews', 'type' => 'Collection<ProductRate>'],
        ['key' => 'mediaItems', 'type' => 'array', 'desc' => 'image/video URLs'],
        ['key' => 'primaryCategory', 'type' => 'Category|null', 'children' => $categoryShape],
        ['key' => 'categoryAncestors', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'cartUrl', 'type' => 'string', 'desc' => 'route'],
        ['key' => 'cartAddUrl', 'type' => 'string', 'desc' => 'route, POST target'],
        ['key' => 'qty', 'type' => 'int', 'desc' => 'current selector value'],
        ['key' => 'soldCount', 'type' => 'int'],
        ['key' => 'related', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'alsoViewed', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'recommended', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'deliveryFrom', 'type' => 'int', 'desc' => 'days'],
        ['key' => 'deliveryTo', 'type' => 'int', 'desc' => 'days'],
        ['key' => 'variantData', 'type' => 'array', 'desc' => 'JS-consumable map, keyed by variant id', 'children' => $variantDataEntryShape],
        ['key' => 'shippingCountries', 'type' => 'Collection<Country>'],
    ];

    // category (pages/category.blade.php)
    $categoryVars = [
        ['key' => 'category', 'type' => 'Category|null', 'children' => $categoryShape],
        ['key' => 'products', 'type' => 'LengthAwarePaginator<Product>', 'children' => $productShape],
        ['key' => 'categories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'parentCategory', 'type' => 'Category|null', 'children' => $categoryShape],
        ['key' => 'relatedCategories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'keyword', 'type' => 'string'],
        ['key' => 'sort', 'type' => 'string'],
        ['key' => 'availability', 'type' => 'string'],
        ['key' => 'productFlag', 'type' => 'string'],
        ['key' => 'sale', 'type' => 'bool'],
        ['key' => 'ratings', 'type' => 'string'],
        ['key' => 'min', 'type' => 'string'],
        ['key' => 'max', 'type' => 'string'],
        ['key' => 'hasMore', 'type' => 'bool'],
        ['key' => 'allCategories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
    ];

    // cart (pages/cart.blade.php)
    $cartVars = [
        ['key' => 'cartItems', 'type' => 'array<key,item>', 'desc' => "key format: 'v_{variantId}' or 'p_{productId}'", 'children' => $cartItemShape],
        ['key' => 'cartTotal', 'type' => 'float'],
        ['key' => 'appliedCoupon', 'type' => 'Coupon|null'],
        ['key' => 'cartDiscount', 'type' => 'float'],
        ['key' => 'cartFinalTotal', 'type' => 'float'],
        ['key' => 'cartWeight', 'type' => 'float', 'desc' => 'grams'],
        ['key' => 'shippingThreshold', 'type' => 'float', 'desc' => 'grams, for free-shipping progress'],
        ['key' => 'shippingCost', 'type' => 'float'],
        ['key' => 'cartTax', 'type' => 'float'],
        ['key' => 'orderTotal', 'type' => 'float'],
        ['key' => 'recommended', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'hotDeals', 'type' => 'Collection<Product>', 'children' => $productShape],
        ['key' => 'relatedProducts', 'type' => 'Collection<Product>', 'children' => $productShape],
    ];

    // checkout (pages/checkout.blade.php)
    $checkoutDataShape = [
        ['key' => 'shipping', 'type' => 'object', 'children' => [
            ['key' => 'name', 'type' => 'string'],
            ['key' => 'email', 'type' => 'string'],
            ['key' => 'phone', 'type' => 'string'],
            ['key' => 'address', 'type' => 'string'],
        ]],
        ['key' => 'billing', 'type' => 'object', 'children' => [
            ['key' => 'same_as_shipping', 'type' => 'bool'],
            ['key' => 'name', 'type' => 'string'],
            ['key' => 'email', 'type' => 'string'],
            ['key' => 'phone', 'type' => 'string'],
            ['key' => 'address', 'type' => 'string'],
        ]],
        ['key' => 'payment', 'type' => 'object', 'children' => [
            ['key' => 'method', 'type' => 'string'],
        ]],
    ];
    $checkoutVars = [
        ['key' => 'data', 'type' => 'object', 'children' => $checkoutDataShape, 'open' => true],
        ['key' => 'cartItems', 'type' => 'array<key,item>', 'desc' => 'same shape as cart page', 'children' => $cartItemShape],
        ['key' => 'paymentGateways', 'type' => 'Collection<PaymentGateway>'],
        ['key' => 'countries', 'type' => 'Collection<Country>'],
        ['key' => 'savedAddresses', 'type' => 'Collection<CustomerAddress>', 'desc' => 'logged-in customers only'],
    ];

    // auth (pages/auth.blade.php)
    $authVars = [
        ['key' => 'tab', 'type' => 'string', 'desc' => "'login' | 'register'"],
    ];

    // profile (pages/profile.blade.php)
    $countryShape = [
        ['key' => 'id', 'type' => 'int'],
        ['key' => 'name', 'type' => 'string'],
        ['key' => 'flag_emoji', 'type' => 'string'],
    ];
    $profileVars = [
        ['key' => 'customer', 'type' => 'Model', 'desc' => 'Customer model'],
        ['key' => 'orders', 'type' => 'Collection<Order>'],
        ['key' => 'returnRequests', 'type' => 'Collection<ReturnRequest>'],
        ['key' => 'activeTab', 'type' => 'string'],
        ['key' => 'addresses', 'type' => 'Collection<CustomerAddress>'],
        ['key' => 'countries', 'type' => 'Collection<Country>', 'children' => $countryShape],
        ['key' => 'reviewedProductIds', 'type' => 'array<int>'],
    ];

    // favorites (pages/favorites.blade.php)
    $favoritesVars = [
        ['key' => 'favoriteProducts', 'type' => 'Collection<Product>', 'children' => $productShape],
    ];

    // order-status (pages/order-status.blade.php)
    $orderShape = [
        ['key' => 'uuid', 'type' => 'string'],
        ['key' => 'items', 'type' => 'Collection<OrderItem>'],
        ['key' => 'grand_total', 'type' => 'float'],
        ['key' => 'status', 'type' => 'string'],
        ['key' => 'shipping_address', 'type' => 'string'],
    ];
    $orderStatusVars = [
        ['key' => 'order', 'type' => 'Model', 'desc' => 'Order model', 'children' => $orderShape],
        ['key' => 'reviewedProductIds', 'type' => 'array<int>'],
    ];

    // order-tracking (pages/order-tracking.blade.php)
    $orderTrackingVars = [
        ['key' => 'order', 'type' => 'Model', 'desc' => 'Order model'],
    ];

    // order-return (pages/order-return.blade.php)
    $orderReturnVars = [
        ['key' => 'order', 'type' => 'Model', 'desc' => 'Order model'],
        ['key' => 'item', 'type' => 'Model', 'desc' => 'OrderItem being returned'],
        ['key' => 'reasons', 'type' => 'array<ReturnReason>', 'desc' => 'enum cases', 'children' => [
            ['key' => 'value', 'type' => 'string'],
            ['key' => 'label', 'type' => 'string'],
        ]],
    ];

    // page (pages/page.blade.php) — static CMS pages
    $pageVars = [
        ['key' => 'page', 'type' => 'Model', 'desc' => 'Page model', 'children' => [
            ['key' => 'title', 'type' => 'string'],
            ['key' => 'content', 'type' => 'string'],
            ['key' => 'slug', 'type' => 'string'],
        ]],
    ];

    // best-selling / new-in
    $bestSellingVars = [
        ['key' => 'products', 'type' => 'LengthAwarePaginator<Product>', 'children' => $productShape],
        ['key' => 'categories', 'type' => 'Collection<Category>', 'children' => $categoryShape],
        ['key' => 'search', 'type' => 'string'],
        ['key' => 'currentCategoryId', 'type' => 'int|null'],
        ['key' => 'hasMore', 'type' => 'bool'],
    ];
@endphp

<h2>Theme Variable Reference</h2>
<p>
    Every variable available in each storefront page view, with type and shape — rendered as an
    explorable tree. Click any row with a chevron to expand it and see the shape of that value.
    All pages also receive the <a href="#shared">shared layout variables</a> below via the
    platform's view composer — you don't need to pass these yourself.
</p>

<div class="jt-legend">
    <span class="jt-type-badge jt-type-string">string</span>
    <span class="jt-type-badge jt-type-number">int / float</span>
    <span class="jt-type-badge jt-type-bool">bool</span>
    <span class="jt-type-badge jt-type-collection">Collection</span>
    <span class="jt-type-badge jt-type-model">Model / object</span>
    <span class="jt-type-badge jt-type-method">method()</span>
    <span class="jt-type-badge jt-type-array">array</span>
    <span class="jt-type-badge jt-type-generic jt-nullable">nullable</span>
</div>

<h3 id="shared">Shared on every page <span class="docs-badge">layout + all pages</span></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$sharedVars" />
</div>

<hr class="docs-hr">

<h3>home <code>pages/home/index.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$homeVars" />
</div>

<hr class="docs-hr">

<h3>product <code>pages/product.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$productVars" />
</div>

<hr class="docs-hr">

<h3>category <code>pages/category.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$categoryVars" />
</div>

<hr class="docs-hr">

<h3>cart <code>pages/cart.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$cartVars" />
</div>

<hr class="docs-hr">

<h3>checkout <code>pages/checkout.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$checkoutVars" />
</div>
<p><em>
    Checkout fields use Livewire's dotted binding — form inputs use
    <code>wire:model="data.shipping.name"</code> etc. if you keep it as a Livewire-driven page.
    A pure Blade/JS checkout requires wiring your own POST submission — contact support if you
    need a documented checkout POST endpoint outside Livewire.
</em></p>

<hr class="docs-hr">

<h3>auth <code>pages/auth.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$authVars" />
</div>

<hr class="docs-hr">

<h3>profile <code>pages/profile.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$profileVars" />
</div>

<hr class="docs-hr">

<h3>favorites <code>pages/favorites.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$favoritesVars" />
</div>

<hr class="docs-hr">

<h3>order-status <code>pages/order-status.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$orderStatusVars" />
</div>

<hr class="docs-hr">

<h3>order-tracking <code>pages/order-tracking.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$orderTrackingVars" />
</div>

<hr class="docs-hr">

<h3>order-return <code>pages/order-return.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$orderReturnVars" />
</div>

<hr class="docs-hr">

<h3>page <code>pages/page.blade.php</code> <span class="docs-badge">static CMS pages</span></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$pageVars" />
</div>

<hr class="docs-hr">

<h3>best-selling / new-in <code>pages/best-selling.blade.php</code>, <code>pages/new-in.blade.php</code></h3>
<div class="jt-panel">
    <x-json-tree :nodes="$bestSellingVars" />
</div>

<hr class="docs-hr">

<h3>404 <code>pages/404.blade.php</code></h3>
<p>No page-specific variables — only the shared layout variables above.</p>

<style>
.docs-hr { border: none; border-top: 1px solid var(--border2); margin: 28px 0; }

.jt-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding: 12px 14px;
    background: var(--surface-2);
    border: 1px solid var(--border2);
    border-radius: 10px;
    margin: 0 0 20px;
}

.jt-panel {
    background: var(--surface-2);
    border: 1px solid var(--border2);
    border-radius: 10px;
    padding: 10px 14px;
    margin: 0 0 8px;
    overflow-x: auto;
}

.jt-tree, .jt-tree ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.jt-tree ul {
    padding-left: 20px;
    border-left: 1px dashed var(--border2);
    margin-left: 6px;
}
.jt-tree-root {
    padding: 4px 0;
}

.jt-node { position: relative; }

.jt-leaf, details > summary {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 5px 6px;
    border-radius: 6px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12.5px;
    line-height: 1.5;
    cursor: default;
}
details > summary { cursor: pointer; }
.jt-leaf:hover, details > summary:hover { background: var(--surface-3, rgba(127,127,127,0.08)); }

summary { list-style: none; }
summary::-webkit-details-marker { display: none; }

.jt-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--t3);
    flex-shrink: 0;
    margin: 0 5px 0 1px;
}

.jt-caret {
    width: 0; height: 0;
    border-top: 4px solid transparent;
    border-bottom: 4px solid transparent;
    border-left: 5px solid var(--t3);
    flex-shrink: 0;
    margin-right: 3px;
    transition: transform 0.15s ease;
}
details[open] > summary .jt-caret {
    transform: rotate(90deg);
}

.jt-key {
    color: var(--t1);
    font-weight: 700;
}
.jt-colon {
    color: var(--t3);
    margin-left: -6px;
}

.jt-type-badge {
    display: inline-block;
    padding: 1px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    font-family: 'Courier New', Courier, monospace;
    white-space: nowrap;
    border: 1px solid transparent;
}
.jt-type-string    { background: rgba(34,197,94,0.12);  color: #16a34a; border-color: rgba(34,197,94,0.25); }
.jt-type-number     { background: rgba(59,130,246,0.12); color: #2563eb; border-color: rgba(59,130,246,0.25); }
.jt-type-bool       { background: rgba(168,85,247,0.12); color: #9333ea; border-color: rgba(168,85,247,0.25); }
.jt-type-collection { background: rgba(245,158,11,0.14); color: #d97706; border-color: rgba(245,158,11,0.3); }
.jt-type-model      { background: rgba(99,102,241,0.14); color: #6366f1; border-color: rgba(99,102,241,0.3); }
.jt-type-method     { background: rgba(20,184,166,0.14); color: #0d9488; border-color: rgba(20,184,166,0.3); }
.jt-type-array      { background: rgba(236,72,153,0.12); color: #db2777; border-color: rgba(236,72,153,0.25); }
.jt-type-generic    { background: var(--surface-3, rgba(127,127,127,0.14)); color: var(--t2); border-color: var(--border2); }

.jt-nullable { position: relative; padding-right: 10px; }
.jt-nullable::after {
    content: '?';
    margin-left: 3px;
    opacity: 0.7;
}

.jt-desc {
    color: var(--t3);
    font-size: 11.5px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
