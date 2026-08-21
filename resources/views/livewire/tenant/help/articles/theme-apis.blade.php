<h2>Theme Action APIs</h2>
<p>JSON endpoints your theme's JavaScript can call directly — no Livewire required.</p>

<h3>Add to cart</h3>
<div class="docs-code-block">
<pre>POST /cart/add
Content-Type: application/json
X-CSRF-TOKEN: {token from &lt;meta name="csrf-token"&gt;}

{ "product_id": 501, "variant_id": 12, "qty": 1 }

→ 200 { "success": true, "itemName": "Blue T-Shirt", "qty": 1,
        "cartCount": 3, "shippingThreshold": 5000,
        "cartWeightGrams": 900, "shippingPct": 18,
        "remainingForFreeShipping": 4100 }
→ 422 { "success": false, "message": "Only 2 left in stock." }</pre>
</div>

<h3>Remove from cart</h3>
<div class="docs-code-block">
<pre>POST /cart/remove
{ "key": "v_12" }

→ 200 { "success": true, "cart_count": 2 }</pre>
</div>

<h3>Update cart quantity</h3>
<div class="docs-code-block">
<pre>POST /cart/update
{ "key": "v_12", "qty": 3 }

→ 200 { "success": true, "cart_count": 4 }
→ 404 { "success": false, "message": "Item not in cart." }</pre>
</div>

<h3>Toggle favorite (requires login)</h3>
<div class="docs-code-block">
<pre>POST /favorites/toggle
{ "product_id": 501 }

→ 200 { "success": true, "favorited": true, "count": 5 }
→ 401 { "success": false, "message": "Login required." }</pre>
</div>

<h3>List favorited products</h3>
<div class="docs-code-block">
<pre>GET /favorites/list

→ 200 { "products": [
    { "id": 501, "slug": "blue-t-shirt", "name": "Blue T-Shirt",
      "image": "https://...", "price": 29.99, "url": "https://.../products/blue-t-shirt" }
  ] }</pre>
</div>

<h3>Get favorited product IDs (for marking hearts on listing pages)</h3>
<div class="docs-code-block">
<pre>GET /favorites/ids

→ 200 { "ids": [501, 490, 233] }</pre>
</div>

<h3>Search autocomplete</h3>
<div class="docs-code-block">
<pre>GET /search/autocomplete?q=shirt

→ 200 { "products": [
    { "name": "Blue T-Shirt", "slug": "blue-t-shirt",
      "url": "...", "image": "...", "price": "$29.99",
      "original_price": "$39.99|null",
      "has_discount": true, "discount_percentage": 25 }
  ] }</pre>
</div>

<h3>Tracking helpers</h3>
<div class="docs-code-block">
<pre>window.trackViewContent({ content_ids: ['501'], value: 29.99, currency: 'USD' });
window.trackAddToCart({ content_ids: ['501'], value: 29.99, currency: 'USD', num_items: 1 });
window.trackInitiateCheckout({ value: 59.98, currency: 'USD' });
window.trackPurchase({ content_ids: ['501'], value: 29.99, currency: 'USD', order_id: 'ORD-001' });</pre>
</div>

<h3>Standard example — add-to-cart button in vendor JS</h3>
<div class="docs-code-block">
<pre>async function addToCart(productId, variantId, qty) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const res = await fetch('/cart/add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ product_id: productId, variant_id: variantId, qty }),
    });
    const data = await res.json();
    if (data.success) {
        document.getElementById('cart-count').textContent = data.cartCount;
        if (window.trackAddToCart) window.trackAddToCart({ content_ids: [String(productId)], num_items: qty });
    }
}</pre>
</div>
