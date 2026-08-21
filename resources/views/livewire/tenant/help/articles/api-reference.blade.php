<h2>Storefront API</h2>
<p>
    A REST API for calling your store's cart and favorites actions from an
    external client (a mobile app, a headless frontend, a third-party
    integration) — no domain-based tenancy resolution or session cookies
    required to identify the store.
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

<h3>Notes</h3>
<ul>
    <li>The cart itself is scoped to the shopper's browser session on the
        server, so <code>/cart/*</code> calls need the token above and a
        persistent session (cookies) between requests from the same client.</li>
    <li><code>/favorites/*</code> additionally requires the shopper to be
        authenticated on the <code>storefront</code> guard — log them in via
        your existing storefront login flow before calling these.</li>
</ul>
