<h2>Settlements & Payouts</h2>

<h3>Two flows, opposite directions</h3>
<table>
    <tr>
        <th></th>
        <th>Tenant → Central (Settlement)</th>
        <th>Central → Tenant (Payout)</th>
    </tr>
    <tr>
        <td>Trigger</td>
        <td>Tenant clicks "Pay Central" on a paid order with <code>vendor_cost &gt; 0</code></td>
        <td>Central admin records a payout</td>
    </tr>
    <tr>
        <td>Real charge?</td>
        <td>Yes — through configured central gateway</td>
        <td>No — record-only (admin enters bank/gateway info)</td>
    </tr>
    <tr>
        <td>Central table</td>
        <td><code>vendor_settlements</code></td>
        <td><code>tenant_payouts</code></td>
    </tr>
    <tr>
        <td>Invoice prefix</td>
        <td><code>VS-</code></td>
        <td><code>TP-</code></td>
    </tr>
    <tr>
        <td>Side effect on order</td>
        <td>Sets <code>orders.vendor_settled_at</code></td>
        <td>None on order rows</td>
    </tr>
    <tr>
        <td>Email to tenant</td>
        <td><code>VendorSettlementMail</code></td>
        <td><code>TenantPayoutMail</code></td>
    </tr>
    <tr>
        <td>Email to central admins</td>
        <td><code>VendorSettlementAdminMail</code></td>
        <td>—</td>
    </tr>
    <tr>
        <td>Listing page</td>
        <td><code>/admin/vendor-settlements</code></td>
        <td><code>/admin/finance/reports</code> → <em>Payouts</em> tab</td>
    </tr>
</table>

<h3>Vendor settlement controller</h3>
<p><code>App\Http\Controllers\Tenant\VendorSettlementPaymentController</code>:</p>
<ul>
    <li><code>charge()</code> — opens the gateway flow using <code>PaymentManager::centralGateway()</code>.</li>
    <li><code>success()</code> — verifies and forwards to <code>handleSuccess()</code>.</li>
    <li><code>handleSuccess()</code> — runs <code>tenancy()->central(...)</code> to write the settlement row, sets
        <code>vendor_settled_at</code>, sends both emails.</li>
    <li><code>cancel()</code> — redirects back to the dedicated settle page with a flash message.</li>
</ul>

<h3>PaymentManager::centralGateway()</h3>
<p>From a tenant request, this method fetches the central gateway record (using <code>tenancy()->central(...)</code>)
    and instantiates the driver with central credentials. Use it whenever you need to charge into the platform owner's
    account, not the tenant's.</p>

<h3>Tenant payout creation</h3>
<p>Page: <code>App\Livewire\Admin\Finance\TenantPayoutPage</code> at
    <code>/admin/finance/payouts/create/{tenant}</code>. Permission: <code>billing.payouts.manage</code>.</p>
<p>The form uses Livewire validation, persists a <code>TenantPayout</code> row, attaches a snapshot of the latest 20
    paid orders into <code>orders_snapshot</code> as audit data, and sends <code>TenantPayoutMail</code> to all active
    tenant admins (best-effort).</p>