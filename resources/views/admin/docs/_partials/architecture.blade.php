<h2>Architecture & Stack</h2>

<h3>Runtime</h3>
<table>
    <tr>
        <th>Layer</th>
        <th>Technology</th>
        <th>Notes</th>
    </tr>
    <tr>
        <td>Framework</td>
        <td>Laravel 11</td>
        <td>see <code>composer.json</code></td>
    </tr>
    <tr>
        <td>UI</td>
        <td>Livewire v3 + Blade</td>
        <td>Vite for assets (<code>vite.config.js</code>)</td>
    </tr>
    <tr>
        <td>Multi-tenancy</td>
        <td>stancl/tenancy</td>
        <td>One DB per tenant</td>
    </tr>
    <tr>
        <td>Queues</td>
        <td>Database queue</td>
        <td>see <code>config/queue.php</code> + <code>php artisan queue:work</code></td>
    </tr>
    <tr>
        <td>Mail</td>
        <td>Symfony Mailer</td>
        <td>per-tenant config can override</td>
    </tr>
    <tr>
        <td>Payment Gateways</td>
        <td>Custom adapters under <code>app/PaymentGateway/</code></td>
        <td>Stripe, PayPal, Razorpay, Mollie, Iyzico, Authorize.Net, 2Checkout, Midtrans</td>
    </tr>
    <tr>
        <td>Python helpers</td>
        <td>FastAPI/Flask scripts in <code>python-modules/</code></td>
        <td>price finder + social post generator</td>
    </tr>
</table>

<h3>Top-level folders</h3>
<table>
    <tr>
        <th>Path</th>
        <th>Purpose</th>
    </tr>
    <tr>
        <td><code>app/Livewire/Admin/</code></td>
        <td>Central admin Livewire pages</td>
    </tr>
    <tr>
        <td><code>app/Livewire/Tenant/</code></td>
        <td>Tenant admin + storefront Livewire pages</td>
    </tr>
    <tr>
        <td><code>app/Livewire/Website/</code></td>
        <td>Central public website pages (landing, pricing, blog…)</td>
    </tr>
    <tr>
        <td><code>app/Models/</code></td>
        <td>Central models. Tenant models live in <code>app/Models/Tenant/</code></td>
    </tr>
    <tr>
        <td><code>app/Services/</code></td>
        <td>Domain services (calculations, aggregations, integrations)</td>
    </tr>
    <tr>
        <td><code>app/Repositories/</code></td>
        <td>Read-side data access used by Livewire pages</td>
    </tr>
    <tr>
        <td><code>app/PaymentGateway/</code></td>
        <td>Gateway adapters + <code>PaymentManager</code></td>
    </tr>
    <tr>
        <td><code>app/Helpers/</code></td>
        <td>Static helpers like <code>AdminNavigation</code>, <code>TenantNavigation</code></td>
    </tr>
    <tr>
        <td><code>app/Mail/</code></td>
        <td>Mailable classes</td>
    </tr>
    <tr>
        <td><code>resources/views/livewire/admin/</code></td>
        <td>Central admin Blade views</td>
    </tr>
    <tr>
        <td><code>resources/views/livewire/tenant/</code></td>
        <td>Tenant admin Blade views</td>
    </tr>
    <tr>
        <td><code>resources/views/themes/&lt;theme&gt;/</code></td>
        <td>Storefront themes (souqify, ecommet, elora…)</td>
    </tr>
    <tr>
        <td><code>resources/views/emails/</code></td>
        <td>Email body Blade views</td>
    </tr>
    <tr>
        <td><code>routes/central.php</code></td>
        <td>Central domain routes (website + <code>/admin/*</code>)</td>
    </tr>
    <tr>
        <td><code>routes/tenant.php</code></td>
        <td>Tenant routes (storefront + <code>/admin/*</code> on tenant domain)</td>
    </tr>
    <tr>
        <td><code>database/migrations/</code></td>
        <td>Central DB migrations</td>
    </tr>
    <tr>
        <td><code>database/migrations/tenant/</code></td>
        <td>Per-tenant migrations (run on tenant create)</td>
    </tr>
</table>

<h3>Bootstrap</h3>
<p>
    The Laravel bootstrap is in <code>bootstrap/app.php</code> and registers
    middleware aliases including <code>admin.permission</code>
    (<code>App\Http\Middleware\AdminPermission</code>) and
    <code>tenant.permission</code> (<code>App\Http\Middleware\TenantPermission</code>).
</p>