<h2>Finance & Calculations</h2>

<p>This page is the canonical reference for every monetary number on the platform. Run the queries against your database
    and you should reproduce these values exactly.</p>

<h3>Order columns (tenant DB <code>orders</code> table)</h3>
<table>
    <tr>
        <th>Column</th>
        <th>Meaning</th>
        <th>Source migration</th>
    </tr>
    <tr>
        <td><code>subtotal</code></td>
        <td>Σ items.sub_total before discounts</td>
        <td>2026_03_26 create_tenant_panel_tables</td>
    </tr>
    <tr>
        <td><code>items_discount</code></td>
        <td>Σ per-item discounts (line-level coupons, sales)</td>
        <td>2026_03_26</td>
    </tr>
    <tr>
        <td><code>discount_amount</code></td>
        <td>Order-level coupon/discount amount</td>
        <td>2026_04_06 add_financial_columns</td>
    </tr>
    <tr>
        <td><code>discount_percentage</code></td>
        <td>Snapshot of percentage if used</td>
        <td>2026_04_06</td>
    </tr>
    <tr>
        <td><code>items_tax</code></td>
        <td>Σ per-item tax</td>
        <td>2026_03_26</td>
    </tr>
    <tr>
        <td><code>tax_amount</code></td>
        <td>Order-level tax</td>
        <td>2026_04_06</td>
    </tr>
    <tr>
        <td><code>tax_percentage</code></td>
        <td>Snapshot of order-level tax %</td>
        <td>2026_04_06</td>
    </tr>
    <tr>
        <td><code>shipping_charge</code></td>
        <td>Shipping charged on the order</td>
        <td>2026_04_06</td>
    </tr>
    <tr>
        <td><code>resolved_shipping_charge</code></td>
        <td>Accessor that resolves shipping after free-shipping rules</td>
        <td>Order model</td>
    </tr>
    <tr>
        <td><code>grand_total</code></td>
        <td>Final amount the customer paid</td>
        <td>2026_03_26</td>
    </tr>
    <tr>
        <td><code>platform_profit</code></td>
        <td>Platform's share of the order (cost + fixed shipping + shipping + tax, less central-owned discounts)</td>
        <td>2026_10_19</td>
    </tr>
    <tr>
        <td><code>vendor_profit</code></td>
        <td>Vendor's share of the order (Σ profit_amount × qty, less vendor-owned discounts)</td>
        <td>2026_10_19</td>
    </tr>
    <tr>
        <td><code>vendor_gateway_id</code></td>
        <td>FK to central gateway used for tenant→central settlement</td>
        <td>2026_04_25</td>
    </tr>
    <tr>
        <td><code>vendor_gateway_fee</code></td>
        <td>Settlement gateway fee snapshot</td>
        <td>2026_04_25</td>
    </tr>
    <tr>
        <td><code>vendor_settled_at</code></td>
        <td>Timestamp tenant paid central for this order</td>
        <td>2026_04_25</td>
    </tr>
    <tr>
        <td><code>payment_gateway_id</code></td>
        <td>Tenant's gateway used by the customer</td>
        <td>2026_04_06</td>
    </tr>
    <tr>
        <td><code>paid</code> (accessor)</td>
        <td>True when payment_status = Paid</td>
        <td>Order model</td>
    </tr>
</table>

<h3>Per-tenant aggregates (paid orders only unless noted)</h3>
<pre>gross_sales              = Σ orders.grand_total           (all orders)
collected_sales          = Σ orders.grand_total           (paid only)
subtotal_total           = Σ orders.subtotal
items_discount_total     = Σ orders.items_discount
discount_total           = Σ orders.discount_amount
items_tax_total          = Σ orders.items_tax
tax_total                = Σ orders.tax_amount
shipping_total           = Σ orders.resolved_shipping_charge
platform_profit_total    = Σ platform_profit   (via OrderProfitCalculator, void orders = 0)
vendor_profit_total      = Σ vendor_profit     (net of partial refunds)
vendor_gateway_fee_total = Σ orders.vendor_gateway_fee</pre>

<h3>Direction balances</h3>
<pre>vendor_own_platform_total = Σ vendor_own_platform
   per order: platform_profit − Σ paid VendorSettlement.product_cost  [collected_by = vendor]

platform_own_vendor_total = Σ platform_own_vendor
   per order: vendor_profit − vendor_profit when payout_released        [collected_by = platform]</pre>
<p>These are the same numbers the vendor panel shows. The legacy keys <code>tenant_owes_central</code> /
    <code>central_owes_tenant</code> are aliases of the two totals above.</p>

<h3>Platform totals</h3>
<p>The four big numbers on the <strong>Tenants Ledger</strong> page (<code>/admin/wallets</code>) are summed across
    <em>every</em> tenant. They are computed by <code>App\Services\Admin\TenantLedgerService::totals()</code>. The same
    service powers the four-tab <strong>Finance Reports</strong> page (<code>/admin/finance/reports</code>).</p>

<h3>Settlement (Tenant → Central)</h3>
<ul>
    <li>Trigger: tenant goes to <em>Vendor Purchases</em> → <em>Pay Central</em> on a paid order with
        <code>vendor_own_platform &gt; 0</code>.</li>
    <li>Tenant chooses a gateway, captures token, redirects to <code>VendorSettlementPaymentController::charge()</code>.
    </li>
    <li>On gateway success, controller writes a row in central <code>vendor_settlements</code> and sets
        <code>orders.vendor_settled_at</code>.</li>
    <li>Emails: <code>VendorSettlementMail</code> to tenant admin, <code>VendorSettlementAdminMail</code> to central
        admins.</li>
    <li>Listing page: <code>/admin/vendor-settlements</code> (permission <code>billing.vendor-settlements.view</code>).
    </li>
</ul>

<h3>Payout (Central → Tenant)</h3>
<ul>
    <li>Record-only. Admin opens <code>/admin/finance/payouts/create/{tenant}</code>.</li>
    <li>Form captures invoice number, method (bank | gateway), bank/gateway name, optional account/IBAN, transaction
        reference, amount, paid_at.</li>
    <li>On submit a row is written to central <code>tenant_payouts</code>; <code>TenantPayoutMail</code> is sent to
        active tenant admins.</li>
    <li>Reduces <code>platform_own_vendor_total</code> on the ledger immediately.</li>
</ul>

<h3>Resetting a tenant's balance</h3>
<ol>
    <li>Open <strong>Tenants Ledger</strong> → click <em>Pay Out</em> on the row.</li>
    <li>Enter amount equal to <em>Outstanding to Tenant</em>; submit.</li>
    <li>The tenant's <code>platform_own_vendor_total</code> drops to 0 because the new payout offsets
        <code>vendor_profit</code>.</li>
</ol>
<p>For a forced one-click recompute (no DB writes) — refresh the page; the ledger service computes everything fresh from
    orders on each request.</p>