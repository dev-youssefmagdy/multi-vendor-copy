<h2>Integrations</h2>

<h3>Payment gateways</h3>
<p>Adapters live in <code>app/PaymentGateway/Gateways/</code> and extend <code>AbstractPaymentGateway</code>. They are
    resolved by <code>App\PaymentGateway\PaymentManager</code> from the tenant or central gateway record (DB). Currently
    shipped:</p>
<ul style="columns:2;">
    <li>Stripe</li>
    <li>PayPal</li>
    <li>Authorize.Net</li>
    <li>2Checkout</li>
    <li>Razorpay</li>
    <li>Mollie</li>
    <li>Paystack</li>
    <li>Iyzico</li>
    <li>Midtrans</li>
    <li>Flutterwave</li>
    <li>Mercadopago</li>
    <li>Xendit</li>
    <li>Paytm</li>
    <li>Paytabs</li>
    <li>MyFatoorah</li>
    <li>PhonePe</li>
    <li>Yoco</li>
    <li>Toyyibpay</li>
    <li>Instamojo</li>
    <li>PerfectMoney</li>
</ul>

<h3>Adding a new gateway</h3>
<ol>
    <li>Create <code>app/PaymentGateway/Gateways/&lt;Name&gt;Gateway.php</code> implementing <code>charge()</code>,
        <code>verify()</code>, <code>refund()</code> as needed.</li>
    <li>Register it in <code>PaymentManager</code> (driver factory map).</li>
    <li>Add a row to <code>payment_gateways</code> via the admin UI (Settings → Payment Gateways).</li>
    <li>For inline-card flows (Stripe / Authorize.Net / 2Checkout), follow the JS token pattern used in
        <code>BuyLanguagePage</code> and <code>VendorSettleOrderPage</code>: token is captured in JS and stashed in
        session under <code>pgtoken_*</code> keys before redirecting to the controller.</li>
</ol>

<h3>Mail</h3>
<p>SMTP is configured at <code>/admin/settings/email-configuration</code>. Templates can be edited at
    <code>/admin/settings/email-templates</code>. Hard-coded mailables live under <code>app/Mail/</code> and use Blade
    templates under <code>resources/views/emails/</code>.</p>

<h3>Tenancy</h3>
<p>See the <em>Multi-Tenancy</em> section. The library is <code>stancl/tenancy</code>.</p>

<h3>Python helpers</h3>
<p>Lightweight scripts under <code>python-modules/</code>:</p>
<ul>
    <li><code>app.py</code> — main entry exposing the helper services.</li>
    <li><code>price_finder.py</code> — scrapes/parses competitor prices.</li>
    <li><code>social_post_generator.py</code> — generates social media post copy.</li>
    <li><code>requirements.txt</code> — Python dependencies.</li>
</ul>
<p>They are intended to be invoked from PHP via shell or HTTP. See the module's README for setup.</p>

<h3>Queues & schedule</h3>
<p>Database queue. Run <code>php artisan queue:work --queue=tenant-sync</code> in production. Scheduled jobs are
    registered in <code>app/Console/Kernel.php</code>.</p>