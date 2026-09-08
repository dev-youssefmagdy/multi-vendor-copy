<h2>Storefront API</h2>
<p>
    A complete REST API for your storefront — browsing, cart, favorites,
    reviews, addresses, checkout, coupons, account, and orders — for calling
    from an external client (a mobile app, a headless frontend, a third-party
    integration). No domain-based tenancy resolution is required to identify
    the store; the bearer token below does that.
</p>

<h3>Base URL</h3>
<div class="docs-code-block">
<pre>{{ config('app.url') }}/api/v1</pre>
</div>

<h3>Authentication</h3>
<p>
    Every request must identify your store with a bearer token. The token is
    your store's tenant id, shown below:
</p>
<div class="docs-code-block">
<pre>Authorization: Bearer {{ tenant('id') }}</pre>
</div>
<p>
    Keep this token private — anyone holding it can act on your store's cart
    and, together with a logged-in shopper session, its favorites. Requests
    without a valid token receive <code>401 Unauthorized</code>.
</p>

<h3>Endpoints</h3>

<h4>Add to cart</h4>
<div class="docs-code-block">
<pre>POST /cart/add
Authorization: Bearer {tenant_id}
Content-Type: application/json

{ "product_id": 501, "variant_id": 12, "qty": 1 }

→ 200 { "success": true, "itemName": "Blue T-Shirt", "qty": 1,
        "cartCount": 3, "shippingThreshold": 5000,
        "cartWeightGrams": 900, "shippingPct": 18,
        "remainingForFreeShipping": 4100 }
→ 422 { "success": false, "message": "Only 2 left in stock." }</pre>
</div>

<h4>Remove from cart</h4>
<div class="docs-code-block">
<pre>POST /cart/remove
Authorization: Bearer {tenant_id}

{ "key": "v_12" }

→ 200 { "success": true, "cart_count": 2 }</pre>
</div>

<h4>Update cart quantity</h4>
<div class="docs-code-block">
<pre>POST /cart/update
Authorization: Bearer {tenant_id}

{ "key": "v_12", "qty": 3 }

→ 200 { "success": true, "cart_count": 4 }
→ 404 { "success": false, "message": "Item not in cart." }</pre>
</div>

<h4>Toggle favorite <span class="docs-badge">requires shopper login</span></h4>
<div class="docs-code-block">
<pre>POST /favorites/toggle
Authorization: Bearer {tenant_id}

{ "product_id": 501 }

→ 200 { "success": true, "favorited": true, "count": 5 }
→ 401 { "success": false, "message": "Login required." }</pre>
</div>

<h4>List favorited products <span class="docs-badge">requires shopper login</span></h4>
<div class="docs-code-block">
<pre>GET /favorites/list
Authorization: Bearer {tenant_id}

→ 200 { "products": [
    { "id": 501, "slug": "blue-t-shirt", "name": "Blue T-Shirt",
      "image": "https://...", "price": 29.99, "url": "https://.../products/blue-t-shirt" }
  ] }</pre>
</div>

<h4>Get favorited product IDs <span class="docs-badge">requires shopper login</span></h4>
<div class="docs-code-block">
<pre>GET /favorites/ids
Authorization: Bearer {tenant_id}

→ 200 { "ids": [501, 490, 233] }</pre>
</div>

<h3>Browsing — categories &amp; products</h3>

<h4>List root categories</h4>
<div class="docs-code-block">
<pre>GET /categories
Authorization: Bearer {tenant_id}

→ 200 { "data": [
    { "id": 4, "slug": "electronics", "name": "Electronics",
      "image": "https://...", "children": [ { "id": 9, "slug": "phones", ... } ] }
  ] }</pre>
</div>

<h4>Category detail + products <span class="docs-badge">accepts filters</span></h4>
<div class="docs-code-block">
<pre>GET /categories/{slug}?keyword=&sort=latest&availability=in_stock
    &product_flag=featured&sale=on_sale&ratings=4&section=trending
    &min=10&max=200&per_page=15&page=1
Authorization: Bearer {tenant_id}

→ 200 { "category": { "id": 4, "slug": "electronics", "name": "Electronics", ... },
        "products": { "data": [ { "id": 501, "slug": "blue-t-shirt", "name": "...",
          "image": "...", "price": 29.99, "base_price": 34.99,
          "is_flash_sale": false, "url": "https://..." } ],
          "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 } } }
→ 404 { "success": false, "message": "Category not found." }</pre>
</div>
<p>
    All query params are optional and every param page in the storefront
    (category, search, badge listings) shares this filter set:
</p>
<ul>
    <li><code>keyword</code> — free-text search</li>
    <li><code>sort</code> — <code>latest</code>, <code>old</code>, <code>ascending</code>, <code>descending</code>, <code>rating</code></li>
    <li><code>availability</code> — <code>in_stock</code> or <code>out_of_stock</code></li>
    <li><code>product_flag</code> — <code>featured</code></li>
    <li><code>sale</code> — <code>on_sale</code> or <code>flash_sale</code></li>
    <li><code>ratings</code> — minimum star rating, <code>1</code>–<code>4</code></li>
    <li><code>section</code> — <code>trending</code>, <code>flash_sale</code>, <code>new_arrivals</code>, <code>top_products</code>, <code>explore</code>, <code>featured</code>, <code>hot_deals</code>, <code>special_offers</code>, <code>top_rated</code>, <code>recommended</code></li>
    <li><code>min</code> / <code>max</code> — price range</li>
    <li><code>per_page</code> (max 60) / <code>page</code></li>
</ul>

<h4>List / search products</h4>
<div class="docs-code-block">
<pre>GET /products?keyword=shirt&sort=ascending&per_page=20
Authorization: Bearer {tenant_id}

→ 200 { "products": { "data": [ ... ], "meta": { ... } } }</pre>
</div>

<h4>Product detail</h4>
<div class="docs-code-block">
<pre>GET /products/{slug}
Authorization: Bearer {tenant_id}

→ 200 { "product": { "id": 501, "slug": "blue-t-shirt", "name": "Blue T-Shirt",
    "image": "...", "price": 29.99, "base_price": 34.99, "is_flash_sale": false,
    "url": "...", "description": "...",
    "variants": [ { "id": 12, "title": "Large / Blue", "sku": "BT-L-BLU",
      "active": true, "stock": 8 } ],
    "reviews_summary": { "average": 4.3, "count": 27 } } }
→ 404 { "success": false, "message": "Product not found." }</pre>
</div>

<h3>Reviews</h3>

<h4>List a product's reviews</h4>
<div class="docs-code-block">
<pre>GET /products/{product_id}/reviews
Authorization: Bearer {tenant_id}

→ 200 { "data": [ { "id": 90, "product_id": 501, "customer_name": "Jane Doe",
    "stars": 5, "comment": "Great fit!", "created_at": "2026-01-04T10:00:00+00:00" } ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 },
    "summary": { "average": 5, "count": 1 } }</pre>
</div>

<h4>Submit a review <span class="docs-badge">requires shopper login</span></h4>
<div class="docs-code-block">
<pre>POST /reviews
Authorization: Bearer {tenant_id}

{ "product_id": 501, "stars": 5, "comment": "Great fit!" }

→ 201 { "success": true, "review": { "id": 90, "product_id": 501, "stars": 5, ... } }
→ 422 { "success": false, "message": "You have already reviewed this product." }</pre>
</div>

<h3>Addresses <span class="docs-badge">requires shopper login</span></h3>

<h4>List addresses</h4>
<div class="docs-code-block">
<pre>GET /addresses
Authorization: Bearer {tenant_id}

→ 200 { "data": [ { "id": 3, "label": "Home", "full_name": "Jane Doe",
    "phone": "+1 555 0100", "address_line_1": "1 Main St", "city": "Springfield",
    "state": "IL", "country": "United States", "country_id": 231, "is_default": true } ] }</pre>
</div>

<h4>Create address</h4>
<div class="docs-code-block">
<pre>POST /addresses
Authorization: Bearer {tenant_id}

{ "full_name": "Jane Doe", "address_line_1": "1 Main St", "city": "Springfield",
  "country_id": 231, "phone": "+1 555 0100", "label": "Home", "is_default": true }

→ 201 { "success": true, "address": { "id": 3, ... } }</pre>
</div>

<h4>Update / delete / set default</h4>
<div class="docs-code-block">
<pre>PUT    /addresses/{id}      — same body as create
DELETE /addresses/{id}      → 200 { "success": true }
POST   /addresses/{id}/default → 200 { "success": true }</pre>
</div>

<h3>Checkout <span class="docs-badge">requires shopper login</span></h3>

<h4>Place order</h4>
<div class="docs-code-block">
<pre>POST /checkout
Authorization: Bearer {tenant_id}

{ "payment_method": "cod", "payment_gateway_id": null,
  "coupon_code": "SAVE10",
  "shipping": { "name": "Jane Doe", "email": "jane@example.com",
    "phone": "+1 555 0100", "address": "1 Main St, Springfield, US",
    "address_id": 3, "country_id": 231 } }

→ 201 { "success": true, "requires_payment": false, "payment_gateway_code": null,
    "stock_warnings": [],
    "order": { "uuid": "5f2c...", "status": "pending", "grand_total": 64.98 },
    "companion_order": null }
→ 422 { "success": false, "message": "Your cart is empty." }</pre>
</div>
<p>
    Builds order(s) from the current session cart using the same order-placement
    logic as the storefront checkout page, so it needs a persistent session
    (cookies) carrying the cart between <code>/cart/*</code> calls and this one.
    When <code>requires_payment</code> is <code>true</code>, redirect the
    shopper to your payment flow for <code>payment_gateway_code</code>; COD
    orders are confirmed immediately.
</p>

<h3>Coupons <span class="docs-badge">requires shopper login</span></h3>

<h4>Current coupon / apply / remove</h4>
<div class="docs-code-block">
<pre>GET  /coupon
→ 200 { "coupon": { "code": "SAVE10", "type": "percentage", "value": 10,
    "minimum_spend": 50, "discount_amount": 6.5 } }
→ 200 { "coupon": null }

POST /coupon/apply
{ "code": "SAVE10" }
→ 200 { "success": true, "coupon": { ... } }
→ 422 { "success": false, "message": "This coupon code is invalid or has expired." }

POST /coupon/remove
→ 200 { "success": true }</pre>
</div>

<h3>Account <span class="docs-badge">requires shopper login</span></h3>

<h4>Profile</h4>
<div class="docs-code-block">
<pre>GET /profile
→ 200 { "customer": { "id": 12, "full_name": "Jane Doe", "email": "jane@example.com",
    "phone": "+1 555 0100", "language": "en", "avatar": null } }

PUT /profile
{ "full_name": "Jane Doe", "email": "jane@example.com", "phone": "+1 555 0100" }
→ 200 { "success": true, "customer": { ... } }

POST /profile/password
{ "current_password": "old-pass", "password": "new-pass", "password_confirmation": "new-pass" }
→ 200 { "success": true }
→ 422 { "success": false, "message": "Current password is incorrect." }

POST /logout
→ 200 { "success": true }</pre>
</div>

<h3>Orders <span class="docs-badge">requires shopper login</span></h3>

<h4>List / detail / reorder / cancel</h4>
<div class="docs-code-block">
<pre>GET /orders?status=pending
→ 200 { "data": [ { "uuid": "5f2c...", "status": "pending", "paid": false,
    "payment_method": "cod", "grand_total": 64.98, "created_at": "..." } ] }

GET /orders/{uuid}
→ 200 { "order": { "uuid": "5f2c...", "status": "pending", "subtotal": 58.48,
    "discount_amount": 6.5, "tax_amount": 0, "shipping_charge": 5,
    "shipping_address": { ... }, "coupon_code": "SAVE10",
    "items": [ { "product_id": 501, "product_name": "Blue T-Shirt",
      "variant_id": 12, "qty": 2, "price": 29.24, "sub_total": 58.48 } ] } }

POST /orders/{uuid}/reorder → 200 { "success": true, "cart_count": 2 }
POST /orders/{uuid}/cancel  → 200 { "success": true }
    → 422 { "success": false, "message": "Order not found or cannot be cancelled." }</pre>
</div>
<p>Cancel is only allowed while the order is still <code>pending</code> or <code>processing</code>.</p>

<h3>Notes</h3>
<ul>
    <li>The cart itself is scoped to the shopper's browser session on the
        server, so <code>/cart/*</code>, <code>/checkout</code>, and
        <code>/coupon/*</code> calls need the token above and a persistent
        session (cookies) between requests from the same client.</li>
    <li>Every endpoint tagged "requires shopper login" additionally needs the
        shopper authenticated on the <code>storefront</code> guard — log them
        in via your existing storefront login flow before calling these.
        Unauthenticated requests to these endpoints redirect to the storefront
        login page rather than returning JSON, so keep the shopper's session
        cookie attached throughout.</li>
</ul>
