<h2>Themes & Storefront</h2>

<p>Each tenant has a storefront ("theme") rendered on its own domain. Themes live under
    <code>resources/views/themes/&lt;slug&gt;/</code>. Currently shipped: <strong>souqify</strong>,
    <strong>ecommet</strong>, <strong>elora</strong>.</p>

<h3>Theme selection</h3>
<ul>
    <li>The active theme per tenant is stored on the tenant record (or via the central <em>Templates</em> setting that
        defaults all tenants to one theme).</li>
    <li>Storefront Livewire components resolve the theme slug and call
        <code>view("themes.$theme.pages.$pageName", $data)</code>.</li>
    <li>Each theme owns its own layout under <code>themes/&lt;slug&gt;/layout/</code> and shared partials.</li>
</ul>

<h3>Per-theme page contract</h3>
<p>Every theme should provide the standard pages so storefront routing works. Below is the souqify roster as the
    canonical reference; new themes should match it 1:1.</p>
<table>
    <tr>
        <th>Page</th>
        <th>Purpose</th>
    </tr>
    <tr>
        <td><code>home.blade.php</code></td>
        <td>Landing for the storefront (sliders, featured products, banners).</td>
    </tr>
    <tr>
        <td><code>category.blade.php</code></td>
        <td>Category listing with filters/pagination.</td>
    </tr>
    <tr>
        <td><code>product.blade.php</code></td>
        <td>Single product with variants, gallery, add-to-cart.</td>
    </tr>
    <tr>
        <td><code>new-in.blade.php</code></td>
        <td>Products tagged "New In" centrally.</td>
    </tr>
    <tr>
        <td><code>best-selling.blade.php</code></td>
        <td>Products tagged "Best Selling" centrally.</td>
    </tr>
    <tr>
        <td><code>cart.blade.php</code></td>
        <td>Cart review + line totals + coupon.</td>
    </tr>
    <tr>
        <td><code>checkout.blade.php</code></td>
        <td>Address + gateway selection + place order.</td>
    </tr>
    <tr>
        <td><code>order-status.blade.php</code></td>
        <td>Post-checkout success/failure landing.</td>
    </tr>
    <tr>
        <td><code>order-tracking.blade.php</code></td>
        <td>Customer-facing order tracking.</td>
    </tr>
    <tr>
        <td><code>auth.blade.php</code></td>
        <td>Customer login/register UI.</td>
    </tr>
    <tr>
        <td><code>favorites.blade.php</code></td>
        <td>Wishlist.</td>
    </tr>
    <tr>
        <td><code>profile.blade.php</code></td>
        <td>Customer account & order history.</td>
    </tr>
    <tr>
        <td><code>_product-card.blade.php</code></td>
        <td>Reusable product card partial.</td>
    </tr>
    <tr>
        <td><code>_showcase-card.blade.php</code></td>
        <td>Featured banner partial.</td>
    </tr>
    <tr>
        <td><code>_pagination.blade.php</code></td>
        <td>Theme-styled pagination.</td>
    </tr>
</table>

<h3>Adding a new theme</h3>
<ol>
    <li>Copy <code>resources/views/themes/souqify/</code> to <code>resources/views/themes/&lt;slug&gt;/</code>.</li>
    <li>Re-skin layout + components. Keep page filenames identical so routing keeps working.</li>
    <li>Register the theme record in the central <em>Templates</em> admin page so tenants can select it.</li>
    <li>Add static assets to <code>public/&lt;slug&gt;/</code> if needed and reference via <code>asset()</code>.</li>
</ol>

<h3>Translations & RTL</h3>
<p>Themes should consume <code>__()</code> for every visible string and respect <code>dir="rtl"</code> when locale is
    Arabic. The locale switcher route is <code>/locale/{locale}</code>.</p>