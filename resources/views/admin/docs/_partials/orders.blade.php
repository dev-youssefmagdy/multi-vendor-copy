<h2>Orders & Order Lifecycle</h2>

<h3>Order model</h3>
<ul>
    <li><strong>Tenant model:</strong> <code>App\Models\Tenant\Order</code> with relations <code>items</code>,
        <code>customer</code>, <code>paymentGateway</code>, <code>activities</code>.</li>
    <li><strong>Activities:</strong> append-only audit log of every status/payment change.</li>
    <li><strong>Items:</strong> <code>order_items</code> (qty, price, discount, tax, shipping_fee, sub_total,
        real_price).</li>
</ul>

<h3>Status enums</h3>
<ul>
    <li><code>App\Enums\OrderStatus</code>: Pending, Processing, Shipped, Delivered, Completed, Cancelled, Rejected.
    </li>
    <li><code>App\Enums\OrderPaymentStatus</code>: Unpaid, Paid, Failed, Refunded.</li>
    <li><code>App\Enums\OrderShippingStatus</code>: Pending, InDelivery, Shipped, Delivered, Cancelled. Derived from
        OrderStatus.</li>
</ul>

<h3>Lifecycle</h3>
<ol>
    <li>Customer places the order (cart → checkout). Order is created in tenant DB with computed totals.</li>
    <li>Order goes to gateway flow. On success, payment_status is flipped to Paid, paid_at is set on the order.</li>
    <li>Tenant ships the order. Status transitions through Processing → Shipped → Delivered.</li>
    <li>If <code>vendor_cost &gt; 0</code>, the tenant later settles the cost with central via the vendor settlement
        flow. <code>vendor_settled_at</code> is set on the order, and a <code>VendorSettlement</code> record is written
        centrally.</li>
    <li>If the order was paid into a central-owned gateway, central later issues a <code>TenantPayout</code> for the
        tenant's net share.</li>
</ol>

<h3>Cross-tenant order listing</h3>
<p>The central <code>/admin/orders</code> page iterates all tenants via <code>tenancy()->initialize()</code> and queries
    each tenant DB. See <code>App\Services\Admin\TenantAdminAggregateService::orders()</code>.</p>

<h3>Order-level mappings</h3>
<table>
    <tr>
        <th>Source</th>
        <th>UI field</th>
    </tr>
    <tr>
        <td><code>$order->paid</code></td>
        <td>Payment status badge (Paid / Unpaid / Failed)</td>
    </tr>
    <tr>
        <td><code>$order->paymentGateway?->name</code></td>
        <td>Gateway column on Payment Logs</td>
    </tr>
    <tr>
        <td><code>data_get($order->payment_details, 'transaction_id')</code></td>
        <td>Reference shown to admins</td>
    </tr>
    <tr>
        <td><code>$order->resolved_shipping_charge</code></td>
        <td>Shipping figure on receipts (handles free-shipping)</td>
    </tr>
</table>