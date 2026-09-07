<h2>Multi-Tenancy</h2>

<h3>Library</h3>
<p>Built on <code>stancl/tenancy</code>. Tenants identified by domain (subdomain in dev, full domain in prod).</p>

<h3>Tenant model</h3>
<ul>
    <li><code>App\Models\Tenant</code> — central record of every tenant store.</li>
    <li>Uses <code>tenant{uuid}</code> directories under <code>storage/</code> for per-tenant private files.</li>
    <li>Per-tenant connection is configured automatically by stancl/tenancy when
        <code>tenancy()->initialize($tenant)</code> is called.</li>
</ul>

<h3>Switching contexts from central code</h3>
<pre>foreach (Tenant::query()->get() as $tenant) {
    try {
        tenancy()->initialize($tenant);
        // Run any tenant DB query here, e.g.
        $orders = \App\Models\Tenant\Order::query()->get();
    } finally {
        tenancy()->end();
    }
}</pre>

<h3>Central-only models</h3>
<p>Apply the <code>CentralConnection</code> trait so the model always reads/writes the central DB even when called from
    a tenant request.</p>
<pre>use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantPayout extends Model
{
    use CentralConnection;
}</pre>

<h3>Tenant migrations</h3>
<p>
    Migrations under <code>database/migrations/tenant/</code> are executed against each tenant DB
    when the tenant is created (and via the tenancy migrate command). Place storefront/order/cart
    schema there. Central tables go in <code>database/migrations/</code>.
</p>

<h3>Routes</h3>
<ul>
    <li><code>routes/central.php</code> — public website + central admin (matched by central domain).</li>
    <li><code>routes/tenant.php</code> — tenant storefront + tenant admin (matched by tenant domain).</li>
</ul>