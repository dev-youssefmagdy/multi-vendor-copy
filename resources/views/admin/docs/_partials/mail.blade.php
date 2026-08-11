<h2>Mail</h2>

<h3>Mailables</h3>
<p>All mailables live under <code>app/Mail/</code>. Each has a Blade template in <code>resources/views/emails/</code>.
    Notable mailables:</p>
<table>
    <tr>
        <th>Mailable</th>
        <th>Trigger</th>
        <th>View</th>
    </tr>
    <tr>
        <td><code>VendorSettlementMail</code></td>
        <td>Tenant settles a vendor cost with central</td>
        <td><code>emails.vendor-settlement</code></td>
    </tr>
    <tr>
        <td><code>VendorSettlementAdminMail</code></td>
        <td>Same flow → notifies central admins</td>
        <td><code>emails.vendor-settlement-admin</code></td>
    </tr>
    <tr>
        <td><code>TenantPayoutMail</code></td>
        <td>Central admin records a payout to a tenant</td>
        <td><code>emails.tenant-payout</code></td>
    </tr>
    <tr>
        <td><code>OrderConfirmationMail</code></td>
        <td>Customer places/pays an order</td>
        <td><code>emails.order-confirmation</code></td>
    </tr>
</table>

<h3>SMTP configuration</h3>
<p>Editable in <strong>Settings → Email Configuration</strong>. Stored in central DB and used by a custom mail config
    provider that overrides <code>config('mail.*')</code> at runtime.</p>

<h3>Email templates</h3>
<p><strong>Settings → Email Templates</strong> manages reusable templates that contain placeholder variables (e.g.
    <code>{customer_name}</code>) and are rendered by services like <code>App\Services\EmailTemplateService</code>.</p>

<h3>Sending from central into a tenant context</h3>
<p>Pattern used by <code>TenantPayoutPage::save()</code>:</p>
<pre>tenancy()->initialize($tenant);
try {
    $emails = AdminUser::where('status', 'active')->pluck('email')->toArray();
} finally {
    tenancy()->end();
}
foreach ($emails as $email) {
    Mail::to($email)->send(new TenantPayoutMail($payout));
}</pre>

<h3>Queues</h3>
<p>Mailables can be queued by adding the <code>ShouldQueue</code> interface. The default connection is
    <code>database</code>.</p>