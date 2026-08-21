<h2>Theme Variable Reference</h2>
<p>Every variable available in each storefront page view, with type and shape. All pages also receive the <a href="#shared">shared layout variables</a> below via the platform's view composer — you don't need to pass these yourself.</p>

<h3 id="shared">Shared on every page (layout + all pages)</h3>
<div class="docs-code-block">
<pre>{
  "storeName": "string",
  "logoPath": "string|null",
  "cartCount": "int",
  "rootCategories": "Collection&lt;Category&gt;",
  "categories": "Collection&lt;Category&gt;",
  "socialLinks": "Collection&lt;SocialLink&gt;",
  "footerText": "string",
  "footerCopyright": "string",
  "languages": "Collection&lt;Language&gt;",
  "currencies": "Collection&lt;Currency&gt;",
  "currentLanguage": "Language|null",
  "currentCurrency": "Currency|null",
  "hasFreeShipping": "bool",
  "customerCountryId": "int|null"
}</pre>
</div>

<h4>Category shape</h4>
<div class="docs-code-block">
<pre>{
  "id": 12,
  "slug": "electronics",
  "name": "via ->translationValue('name')",
  "image_url": "string|null",
  "children": "Collection&lt;Category&gt; (same shape, one level deep)"
}</pre>
</div>

<h4>Currency shape</h4>
<div class="docs-code-block">
<pre>{
  "code": "USD",
  "symbol": "$",
  "conversion_rate": 1.0,
  "is_default": true
}</pre>
</div>

<h4>SocialLink shape</h4>
<div class="docs-code-block">
<pre>{ "url": "https://instagram.com/...", "icon": "facebook | twitter | instagram | linkedin | youtube | whatsapp" }</pre>
</div>

---

<h3>home (<code>pages/home/index.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "banners": "Collection&lt;Banner&gt;",
  "flash_sales": "Collection&lt;FlashSale&gt;",
  "new_arrivals": "Collection&lt;Product&gt;",
  "recommended_products": "Collection&lt;Product&gt;",
  "best_sellers": "Collection&lt;Product&gt;",
  "trending_products": "Collection&lt;Product&gt;",
  "featured_products": "Collection&lt;Product&gt;"
}</pre>
</div>

<h4>Banner shape</h4>
<div class="docs-code-block">
<pre>{
  "image_path": "string (pass through tenant_asset())",
  "url": "string|null",
  "title": "via ->translationValue('title')",
  "subtitle": "via ->translationValue('subtitle')",
  "button_text": "via ->translationValue('button_text')"
}</pre>
</div>

<h4>FlashSale shape</h4>
<div class="docs-code-block">
<pre>{
  "discount_percentage": 25.0,
  "end_date": "Carbon|null — call ->toIso8601String()",
  "bannerImageUrl()": "method, string|null",
  "products": "Collection&lt;Product&gt;"
}</pre>
</div>

<h4>Product shape (used everywhere)</h4>
<div class="docs-code-block">
<pre>{
  "id": 501,
  "slug": "blue-t-shirt",
  "name": "via ->translationValue('name')",
  "primary_image_url": "string|null (accessor)",
  "storefrontPricing()": {
    "current_price": 29.99,
    "original_price": 39.99,
    "has_discount": true,
    "discount_percentage": 25.0
  }
}</pre>
</div>

---

<h3>product (<code>pages/product.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "product": "Product model",
  "variants": "Collection&lt;ProductVariant&gt;",
  "activeVariant": "ProductVariant|null",
  "pricing": "same shape as storefrontPricing()",
  "sellPrice": "float", "realPrice": "float|null",
  "hasDiscount": "bool", "discountPct": "float",
  "displaySell": "string (formatted)", "displayReal": "string|null",
  "savedAmount": "float",
  "symbol": "string", "rate": "float",
  "isInStock": "bool", "manageStock": "bool", "stockValue": "int|null",
  "weightGrams": "float", "weightDisplay": "string",
  "avgRating": "float", "reviewCount": "int",
  "ratingDistribution": "array&lt;int,int&gt; (star=&gt;count)",
  "latestReviews": "Collection&lt;ProductRate&gt;",
  "mediaItems": "array (image/video URLs)",
  "primaryCategory": "Category|null",
  "categoryAncestors": "Collection&lt;Category&gt;",
  "cartUrl": "string (route)", "cartAddUrl": "string (route, POST target)",
  "qty": "int (current selector value)",
  "soldCount": "int",
  "related": "Collection&lt;Product&gt;",
  "alsoViewed": "Collection&lt;Product&gt;",
  "recommended": "Collection&lt;Product&gt;",
  "deliveryFrom": "int (days)", "deliveryTo": "int (days)",
  "variantData": "array (JS-consumable variant map — id =&gt; {price, stock, image})",
  "shippingCountries": "Collection&lt;Country&gt;"
}</pre>
</div>

---

<h3>category (<code>pages/category.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "category": "Category|null",
  "products": "LengthAwarePaginator&lt;Product&gt;",
  "categories": "Collection&lt;Category&gt;",
  "parentCategory": "Category|null",
  "relatedCategories": "Collection&lt;Category&gt;",
  "keyword": "string", "sort": "string",
  "availability": "string", "productFlag": "string",
  "sale": "bool", "ratings": "string", "min": "string", "max": "string",
  "hasMore": "bool",
  "allCategories": "Collection&lt;Category&gt;"
}</pre>
</div>

---

<h3>cart (<code>pages/cart.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "cartItems": "array&lt;key,item&gt; — key format: 'v_{variantId}' or 'p_{productId}'",
  "cartTotal": "float",
  "appliedCoupon": "Coupon|null",
  "cartDiscount": "float",
  "cartFinalTotal": "float",
  "cartWeight": "float (grams)",
  "shippingThreshold": "float (grams, for free-shipping progress)",
  "shippingCost": "float",
  "cartTax": "float",
  "orderTotal": "float",
  "recommended": "Collection&lt;Product&gt;",
  "hotDeals": "Collection&lt;Product&gt;",
  "relatedProducts": "Collection&lt;Product&gt;"
}</pre>
</div>

<h4>Cart item shape (each value in cartItems)</h4>
<div class="docs-code-block">
<pre>{
  "product_id": 501,
  "variant_id": 12,
  "qty": 2,
  "product": "Product model (name, image, slug)",
  "price": 29.99,
  "subtotal": 59.98
}</pre>
</div>

---

<h3>checkout (<code>pages/checkout.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "data": {
    "shipping": { "name": "", "email": "", "phone": "", "address": "" },
    "billing":  { "same_as_shipping": true, "name": "", "email": "", "phone": "", "address": "" },
    "payment":  { "method": "" }
  },
  "cartItems": "same shape as cart page",
  "paymentGateways": "Collection&lt;PaymentGateway&gt;",
  "countries": "Collection&lt;Country&gt;",
  "savedAddresses": "Collection&lt;CustomerAddress&gt; (logged-in customers only)"
}</pre>
</div>

<p><em>Checkout fields use Livewire's dotted binding — form inputs use <code>wire:model="data.shipping.name"</code> etc. if you keep it as a Livewire-driven page. A pure Blade/JS checkout requires wiring your own POST submission — contact support if you need a documented checkout POST endpoint outside Livewire.</em></p>

---

<h3>auth (<code>pages/auth.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{ "tab": "'login' | 'register'" }</pre>
</div>

---

<h3>profile (<code>pages/profile.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "customer": "Customer model",
  "orders": "Collection&lt;Order&gt;",
  "returnRequests": "Collection&lt;ReturnRequest&gt;",
  "activeTab": "string",
  "addresses": "Collection&lt;CustomerAddress&gt;",
  "countries": "Collection&lt;Country&gt; (id, name, flag_emoji)",
  "reviewedProductIds": "array&lt;int&gt;"
}</pre>
</div>

---

<h3>favorites (<code>pages/favorites.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{ "favoriteProducts": "Collection&lt;Product&gt;" }</pre>
</div>

---

<h3>order-status (<code>pages/order-status.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "order": "Order model (uuid, items, grand_total, status, shipping_address)",
  "reviewedProductIds": "array&lt;int&gt;"
}</pre>
</div>

---

<h3>order-tracking (<code>pages/order-tracking.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{ "order": "Order model" }</pre>
</div>

---

<h3>order-return (<code>pages/order-return.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "order": "Order model",
  "item": "OrderItem being returned",
  "reasons": "array of ReturnReason enum cases (value + label)"
}</pre>
</div>

---

<h3>page (<code>pages/page.blade.php</code>) — static CMS pages</h3>
<div class="docs-code-block">
<pre>{ "page": "Page model (title, content, slug)" }</pre>
</div>

---

<h3>best-selling / new-in (<code>pages/best-selling.blade.php</code>, <code>pages/new-in.blade.php</code>)</h3>
<div class="docs-code-block">
<pre>{
  "products": "LengthAwarePaginator&lt;Product&gt;",
  "categories": "Collection&lt;Category&gt;",
  "search": "string",
  "currentCategoryId": "int|null",
  "hasMore": "bool"
}</pre>
</div>

---

<h3>404 (<code>pages/404.blade.php</code>)</h3>
<p>No page-specific variables — only the shared layout variables above.</p>
