<?php

namespace App\Services\Mail;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateAction;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Mail\HtmlTemplateMail;
use App\Models\EmailTemplate as CentralEmailTemplate;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\Tenant\Currency;
use App\Models\Tenant\EmailTemplate as TenantEmailTemplate;
use App\Models\Tenant\Order;
use App\Models\Tenant\Transaction;
use App\Services\AdminNotificationService;
use App\Services\Mail\MailConfigurationResolver;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class TemplateMailService
{
    public function __construct(
        private readonly MailConfigurationResolver $configurationResolver,
        private readonly MailManager $mailManager,
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    public function sendCentralInvoice(Invoice $invoice, string $pdfBinary, ?string $locale = null): bool
    {
        if (!filled($invoice->customer_email)) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminInvoiceIssued,
            $invoice->customer_email,
            $this->invoiceTokens($invoice),
            [
                [
                    'data' => $pdfBinary,
                    'name' => sprintf('%s.pdf', Str::slug($invoice->invoice_number)),
                    'options' => ['mime' => 'application/pdf'],
                ]
            ],
            $locale,
        );
    }

    public function sendTenantOrderPlaced(Order $order): bool
    {
        return $this->sendTenantTemplate(
            EmailTemplateAction::TenantOrderConfirmation,
            $this->orderRecipient($order),
            $this->orderTokens($order),
            [],
            $this->orderLocale($order),
        );
    }

    public function sendTenantPaymentConfirmed(Order $order): void
    {
        $recipient = $this->orderRecipient($order);

        if (!$recipient) {
            return;
        }

        $locale = $this->orderLocale($order);
        $tokens = $this->orderTokens($order, [
            '{{transaction_status}}' => 'Paid',
            '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
        ]);

        $this->sendTenantTemplate(EmailTemplateAction::TenantOrderProcessing, $recipient, $tokens, [], $locale);
        $this->sendTenantTemplate(EmailTemplateAction::TenantPaymentReceipt, $recipient, $tokens, [], $locale);
    }

    /**
     * Send an order invoice email to the vendor (tenant admin) when a customer's payment is confirmed.
     * Includes full line items, discounts, tax, shipping, and grand total breakdown.
     */
    public function sendVendorOrderInvoice(Order $order): void
    {
        $vendorEmail = tenant()?->email;

        if (!filled($vendorEmail)) {
            return;
        }

        $tokens = $this->orderTokens($order, [
            '{{transaction_status}}' => 'Paid',
            '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
        ]);

        $this->sendTenantTemplate(EmailTemplateAction::TenantPaymentReceipt, $vendorEmail, $tokens);
    }

    public function sendTenantShippingUpdate(Order $order, OrderShippingStatus $shippingStatus): bool
    {
        $action = match ($shippingStatus) {
            OrderShippingStatus::Delivered => EmailTemplateAction::TenantOrderDelivered,
            OrderShippingStatus::Cancelled => EmailTemplateAction::TenantOrderCancelled,
            OrderShippingStatus::InDelivery, OrderShippingStatus::Shipped => EmailTemplateAction::TenantShippingUpdate,
            OrderShippingStatus::Pending => null,
        };

        if (!$action) {
            return false;
        }

        return $this->sendTenantTemplate(
            $action,
            $this->orderRecipient($order),
            $this->orderTokens($order, [
                '{{shipping_status}}' => $shippingStatus->label(),
                '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            ]),
            [],
            $this->orderLocale($order),
        );
    }

    public function sendTenantStatusUpdate(Order $order, OrderStatus $orderStatus): bool
    {
        $action = match ($orderStatus) {
            OrderStatus::Processing => EmailTemplateAction::TenantOrderProcessing,
            OrderStatus::Shipped => EmailTemplateAction::TenantShippingUpdate,
            OrderStatus::Delivered, OrderStatus::Completed => EmailTemplateAction::TenantOrderDelivered,
            OrderStatus::Cancelled, OrderStatus::Rejected => EmailTemplateAction::TenantOrderCancelled,
            OrderStatus::Pending => null,
        };

        if (!$action) {
            return false;
        }

        return $this->sendTenantTemplate(
            $action,
            $this->orderRecipient($order),
            $this->orderTokens($order, [
                '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            ]),
            [],
            $this->orderLocale($order),
        );
    }

    public function sendReturnStatusUpdate(\App\Models\ReturnRequest $returnRequest, Order $order): bool
    {
        return $this->sendTenantTemplate(
            EmailTemplateAction::TenantReturnUpdate,
            $this->orderRecipient($order),
            $this->orderTokens($order, [
                '{{return_status}}' => $returnRequest->status->label(),
                '{{return_reason}}' => $returnRequest->reason->label(),
                '{{processed_at}}' => optional($returnRequest->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            ]),
            [],
            $this->orderLocale($order),
        );
    }

    public function sendCentralTemplate(EmailTemplateAction $action, ?string $recipient, array $tokens = [], array $attachments = [], ?string $locale = null): bool
    {
        if (!filled($recipient)) {
            return false;
        }

        $template = $this->resolveCentralTemplate($action);

        if (!$template) {
            return false;
        }

        $config = $this->configurationResolver->central();
        $mergedTokens = array_replace($this->commonTokens($config), $tokens);

        if ($locale) {
            $template->loadMissing('translations');
            $subject = $this->render($template->translatedSubject($locale), $mergedTokens);
            $body = $this->render($template->translatedBody($locale) ?? '', $mergedTokens);
        } else {
            $subject = $this->render($template->subject, $mergedTokens);
            $body = $this->render($template->body ?? '', $mergedTokens);
        }

        $body = $this->applyDirection($body, $locale);

        if ($this->tryDeliver($config, $recipient, $subject, $body, $attachments)) {
            return true;
        }

        $this->notifyAdminOfDeliveryFailure($action, $recipient);

        return false;
    }

    public function sendTenantTemplate(EmailTemplateAction $action, ?string $recipient, array $tokens = [], array $attachments = [], ?string $locale = null): bool
    {
        if (!filled($recipient)) {
            return false;
        }

        $template = $this->resolveTenantTemplate($action);

        if (!$template) {
            return false;
        }

        $tenantConfig = $this->configurationResolver->tenant();
        $commonTokens = $this->commonTokens($tenantConfig, tenant());

        if ($locale && $template instanceof TenantEmailTemplate) {
            $template->loadMissing('translations');
            $subject = $this->render($template->translatedSubject($locale), array_replace($commonTokens, $tokens));
            $body = $this->render($template->translatedBody($locale) ?? '', array_replace($commonTokens, $tokens));
        } else {
            $subject = $this->render($template->subject, array_replace($commonTokens, $tokens));
            $body = $this->render($template->body ?? '', array_replace($commonTokens, $tokens));
        }

        $body = $this->applyDirection($body, $locale);

        if ($this->tryDeliver($tenantConfig, $recipient, $subject, $body, $attachments)) {
            return true;
        }

        $centralConfig = $this->configurationResolver->central();

        if ($centralConfig !== $tenantConfig && $this->tryDeliver($centralConfig, $recipient, $subject, $body, $attachments)) {
            return true;
        }

        $this->notifyAdminOfDeliveryFailure($action, $recipient);

        return false;
    }

    /**
     * Attempt delivery, swallowing transport-level failures (bad SMTP creds, unreachable host, etc.)
     * so callers can fall back to another mail configuration instead of the request failing.
     */
    protected function tryDeliver(array $config, string $recipient, string $subject, string $body, array $attachments = []): bool
    {
        try {
            return $this->deliver($config, $recipient, $subject, $body, $attachments);
        } catch (Throwable $e) {
            Log::warning('Mail delivery attempt failed', [
                'mailer' => $config['mailer'] ?? null,
                'host' => $config['host'] ?? null,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function notifyAdminOfDeliveryFailure(EmailTemplateAction $action, string $recipient): void
    {
        $this->adminNotificationService->notify(
            'mail_delivery_failed',
            'Email delivery failed',
            sprintf(
                'Could not send "%s" email to %s using either the tenant or central mail configuration.',
                $action->value,
                $recipient,
            ),
            [
                'action' => $action->value,
                'recipient' => $recipient,
                'tenant_id' => tenant()?->id,
            ],
        );
    }

    /**
     * Send a one-off test email using the given (possibly unsaved) mail config,
     * so admins/tenants can verify SMTP settings before saving them.
     */
    public function sendTest(array $config, string $recipient): array
    {
        // Force a real SMTP attempt: this button exists to verify the SMTP fields on the
        // settings page, not whichever transport (e.g. "log") the app happens to default to.
        $config['mailer'] = 'smtp';

        if (!filled($config['host'] ?? null)) {
            return ['success' => false, 'message' => 'Cannot send test email: SMTP host is not configured.'];
        }

        try {
            $this->deliver(
                $config,
                $recipient,
                sprintf('Test Email from %s', config('app.name', 'Multi Vendor')),
                $this->testEmailBody($config),
            );

            return ['success' => true, 'message' => "Test email sent successfully to {$recipient}."];
        } catch (Throwable $e) {
            Log::warning('Test mail delivery failed', [
                'mailer' => $config['mailer'] ?? null,
                'host' => $config['host'] ?? null,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()];
        }
    }

    protected function testEmailBody(array $config): string
    {
        return '<p>This is a test email sent from <strong>' . e(config('app.name', 'Multi Vendor')) . '</strong> to verify your mail configuration.</p>'
            . '<p style="color:#6b7280;font-size:13px;">Mailer: ' . e($config['mailer'] ?? '-')
            . '<br>Host: ' . e($config['host'] ?? '-')
            . '<br>Port: ' . e($config['port'] ?? '-')
            . '<br>Encryption: ' . e($config['encryption'] ?: 'none') . '</p>';
    }

    protected function deliver(array $config, string $recipient, string $subject, string $body, array $attachments = []): bool
    {
        $mailerName = 'runtime_' . Str::random(12);

        config()->set('mail.mailers.' . $mailerName, $this->runtimeMailerConfig($config));

        try {
            $mailer = $this->mailManager->mailer($mailerName);
            $mailable = new HtmlTemplateMail($subject, $body, $attachments);

            if (filled($config['from_address'])) {
                $mailable->from($config['from_address'], $config['from_name'] ?: null);
            }

            $mailer->to($recipient)->send($mailable);

            return true;
        } finally {
            $this->mailManager->purge($mailerName);
            config()->set('mail.mailers.' . $mailerName, null);
        }
    }

    protected function runtimeMailerConfig(array $config): array
    {
        $mailer = strtolower(trim((string) ($config['mailer'] ?? 'smtp'))) ?: 'smtp';
        $base = config('mail.mailers.' . $mailer, ['transport' => $mailer]);

        if ($mailer !== 'smtp') {
            return $base;
        }

        return array_merge($base, [
            'transport' => 'smtp',
            'host' => $config['host'],
            'port' => (int) ($config['port'] ?: 587),
            'username' => $config['username'],
            'password' => $config['password'],
            'encryption' => $config['encryption'] ?: null,
        ]);
    }

    protected function resolveCentralTemplate(EmailTemplateAction $action): ?CentralEmailTemplate
    {
        return $this->onCentral(fn() => CentralEmailTemplate::query()
            ->where('action', $action->value)
            ->where('type', $action->templateType()->value)
            ->where('status', ActivationStatus::Active->value)
            ->first());
    }

    protected function resolveTenantTemplate(EmailTemplateAction $action): CentralEmailTemplate|TenantEmailTemplate|null
    {
        $tenantTemplate = TenantEmailTemplate::query()
            ->where('action', $action->value)
            ->first();

        if ($tenantTemplate) {
            return $tenantTemplate->is_active ? $tenantTemplate : null;
        }

        return $this->resolveCentralTemplate($action);
    }

    protected function render(string $content, array $tokens): string
    {
        $content = strtr($content, $tokens);

        // Hide table rows / list items whose merge tag had no value supplied,
        // instead of leaking the raw {{tag}} placeholder into the sent email.
        $content = preg_replace('/<tr\b(?:(?!<\/tr>).)*\{\{\s*[a-zA-Z0-9_]+\s*\}\}(?:(?!<\/tr>).)*<\/tr>/su', '', $content);
        $content = preg_replace('/<li\b(?:(?!<\/li>).)*\{\{\s*[a-zA-Z0-9_]+\s*\}\}(?:(?!<\/li>).)*<\/li>/su', '', $content);

        return $content;
    }

    /**
     * RTL locales the storefront theme layouts already treat as right-to-left.
     */
    protected function isRtlLocale(?string $locale): bool
    {
        return in_array($locale ?: app()->getLocale(), ['ar', 'he', 'fa'], true);
    }

    protected function applyDirection(string $body, ?string $locale): string
    {
        $rtl = $this->isRtlLocale($locale);
        $dir = $rtl ? 'rtl' : 'ltr';
        $align = $rtl ? 'right' : 'left';

        return '<div dir="' . $dir . '" style="direction:' . $dir . ';text-align:' . $align . ';">' . $body . '</div>';
    }

    protected function commonTokens(array $config, ?Tenant $tenant = null): array
    {
        $portalUrl = $tenant
            ? rtrim(url('/'), '/')
            : rtrim((string) config('app.url'), '/');

        return [
            '{{app_name}}' => config('app.name', 'Multi Vendor'),
            '{{current_year}}' => now()->format('Y'),
            '{{portal_url}}' => $portalUrl,
            '{{support_email}}' => $config['from_address'] ?: ($tenant?->email ?: config('mail.from.address')),
            '{{tenant_name}}' => $tenant?->name ?? '-',
            '{{tenant_email}}' => $tenant?->email ?? '-',
            '{{tenant_domain}}' => $tenant?->domains()->first()?->domain ?? '-',
        ];
    }

    protected function invoiceTokens(Invoice $invoice): array
    {
        return [
            '{{invoice_number}}' => $invoice->invoice_number,
            '{{order_number}}' => $invoice->order_number,
            '{{customer_name}}' => $invoice->customer_name ?: 'Customer',
            '{{customer_email}}' => $invoice->customer_email ?: '-',
            '{{store_name}}' => data_get($invoice->meta, 'store_name', $invoice->tenant?->name ?? 'Store'),
            '{{amount}}' => sprintf('%s %s', $invoice->currency ?: 'USD', number_format((float) $invoice->amount, 2)),
            '{{payment_method}}' => $invoice->payment_method ?: '-',
            '{{payment_reference}}' => $invoice->payment_reference ?: '-',
            '{{issued_at}}' => optional($invoice->issued_at)->format('M d, Y H:i') ?: '-',
        ];
    }

    protected function orderTokens(Order $order, array $overrides = []): array
    {
        $order->loadMissing(['customer', 'paymentGateway', 'items.product', 'activities']);

        $tenant = tenant();
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $paymentDetails = is_array($order->payment_details) ? $order->payment_details : [];
        $currencyCode = Currency::query()->where('is_default', true)->value('code') ?? 'USD';
        $storeName = $tenant?->data['shop_name'] ?? $tenant?->name ?? config('app.name', 'Store');

        $tokens = [
            '{{order_number}}' => $order->uuid,
            '{{customer_name}}' => $shippingAddress['name'] ?? $order->customer?->full_name ?? 'Customer',
            '{{customer_email}}' => $shippingAddress['email'] ?? $order->customer?->email ?? '-',
            '{{store_name}}' => $storeName,
            '{{order_total}}' => sprintf('%s %s', $currencyCode, number_format((float) $order->grand_total, 2)),
            '{{payment_method}}' => $order->paymentGateway?->name ?? $order->payment_method ?? '-',
            '{{payment_reference}}' => $paymentDetails['transaction_id'] ?? data_get($paymentDetails, 'raw.transaction_id') ?? data_get($paymentDetails, 'raw.id') ?? '-',
            '{{order_status}}' => $order->status->label(),
            '{{shipping_status}}' => $this->resolvedShippingStatusLabel($order),
            '{{placed_at}}' => optional($order->created_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            '{{shipping_address}}' => collect([
                $shippingAddress['name'] ?? null,
                $shippingAddress['address'] ?? null,
                $shippingAddress['country'] ?? null,
            ])->filter()->implode(', '),
            '{{carrier_name}}' => '-',
            '{{tracking_number}}' => '-',
            '{{tracking_url}}' => rtrim(url('/'), '/') . '/orders/' . $order->uuid,
            '{{estimated_delivery}}' => '-',
            '{{transaction_amount}}' => sprintf('%s %s', $currencyCode, number_format((float) $order->grand_total, 2)),
            '{{transaction_status}}' => $order->paid ? 'Paid' : 'Pending',
            '{{invoice_number}}' => '-',
            '{{cancellation_reason}}' => $order->status === OrderStatus::Rejected ? 'Rejected' : 'Cancelled',
            '{{transaction_number}}' => $paymentDetails['transaction_id'] ?? $order->uuid,
            '{{transaction_type}}' => $order->paid ? 'Charge' : 'Authorization',
            '{{wallet_balance}}' => '-',
            '{{order_items_table}}' => $this->buildOrderItemsTable($order, $currencyCode),
        ];

        if (tenant() && Route::has('tenant.storefront.order-status')) {
            $tokens['{{portal_url}}'] = route('tenant.storefront.order-status', $order->uuid);
            $tokens['{{tracking_url}}'] = route('tenant.storefront.order-status', $order->uuid);
        }

        if (tenant() && Route::has('tenant.storefront.order-invoice')) {
            $tokens['{{invoice_url}}'] = route('tenant.storefront.order-invoice', $order->uuid);
            $tokens['{{invoice_number}}'] = 'INV-' . strtoupper(substr($order->uuid, 0, 8));
        }

        return array_replace($tokens, $overrides);
    }

    protected function buildOrderItemsTable(Order $order, string $currency): string
    {
        $rows = '';
        foreach ($order->items as $item) {
            $productName = $item->product?->name ?? "Product #{$item->product_id}";
            $unitPrice = number_format((float) $item->price, 2);
            $subtotal = number_format((float) $item->sub_total, 2);
            $discount = (float) $item->discount > 0 ? '-' . number_format((float) $item->discount, 2) : '-';
            $tax = (float) $item->tax > 0 ? number_format((float) $item->tax, 2) : '-';

            $rows .= '<tr style="border-bottom:1px solid #e5e7eb;">'
                . '<td style="padding:8px 10px;font-size:13px;color:#111827;">' . e($productName) . '<br><span style="font-size:11px;color:#6b7280;">Qty: ' . (int) $item->qty . '</span></td>'
                . '<td style="padding:8px 10px;font-size:13px;color:#374151;text-align:right;">' . $currency . ' ' . $unitPrice . '</td>'
                . '<td style="padding:8px 10px;font-size:13px;color:#374151;text-align:right;">' . $discount . '</td>'
                . '<td style="padding:8px 10px;font-size:13px;color:#374151;text-align:right;">' . $tax . '</td>'
                . '<td style="padding:8px 10px;font-size:13px;font-weight:600;color:#111827;text-align:right;">' . $currency . ' ' . $subtotal . '</td>'
                . '</tr>';
        }

        $subtotal = number_format($order->subtotal, 2);
        $itemsDiscount = $order->items_discount > 0 ? number_format($order->items_discount, 2) : null;
        $orderDiscount = $order->discount_amount > 0 ? number_format($order->discount_amount, 2) : null;
        $tax = ($order->items_tax + $order->tax_amount) > 0
            ? number_format($order->items_tax + $order->tax_amount, 2) : null;
        $shipping = $order->resolved_shipping_charge > 0 ? number_format($order->resolved_shipping_charge, 2) : null;
        $grandTotal = number_format($order->grand_total, 2);

        $summaryRows = '<tr><td colspan="4" style="padding:8px 10px;font-size:13px;color:#6b7280;text-align:right;">Subtotal</td><td style="padding:8px 10px;font-size:13px;color:#111827;text-align:right;">' . $currency . ' ' . $subtotal . '</td></tr>';
        if ($itemsDiscount) {
            $summaryRows .= '<tr><td colspan="4" style="padding:4px 10px;font-size:13px;color:#6b7280;text-align:right;">Item Discounts</td><td style="padding:4px 10px;font-size:13px;color:#16a34a;text-align:right;">-' . $currency . ' ' . $itemsDiscount . '</td></tr>';
        }
        if ($orderDiscount) {
            $summaryRows .= '<tr><td colspan="4" style="padding:4px 10px;font-size:13px;color:#6b7280;text-align:right;">Order Discount</td><td style="padding:4px 10px;font-size:13px;color:#16a34a;text-align:right;">-' . $currency . ' ' . $orderDiscount . '</td></tr>';
        }
        if ($tax) {
            $summaryRows .= '<tr><td colspan="4" style="padding:4px 10px;font-size:13px;color:#6b7280;text-align:right;">Tax</td><td style="padding:4px 10px;font-size:13px;color:#374151;text-align:right;">' . $currency . ' ' . $tax . '</td></tr>';
        }
        if ($shipping) {
            $summaryRows .= '<tr><td colspan="4" style="padding:4px 10px;font-size:13px;color:#6b7280;text-align:right;">Shipping</td><td style="padding:4px 10px;font-size:13px;color:#374151;text-align:right;">' . $currency . ' ' . $shipping . '</td></tr>';
        }
        $summaryRows .= '<tr style="border-top:2px solid #e5e7eb;"><td colspan="4" style="padding:10px 10px 6px;font-size:14px;font-weight:700;color:#111827;text-align:right;">Grand Total</td><td style="padding:10px 10px 6px;font-size:14px;font-weight:700;color:#1d4ed8;text-align:right;">' . $currency . ' ' . $grandTotal . '</td></tr>';

        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;font-family:Arial,sans-serif;">'
            . '<thead><tr style="background:#f8fafc;">'
            . '<th style="padding:8px 10px;font-size:12px;color:#6b7280;font-weight:600;text-align:left;">Product</th>'
            . '<th style="padding:8px 10px;font-size:12px;color:#6b7280;font-weight:600;text-align:right;">Unit Price</th>'
            . '<th style="padding:8px 10px;font-size:12px;color:#6b7280;font-weight:600;text-align:right;">Discount</th>'
            . '<th style="padding:8px 10px;font-size:12px;color:#6b7280;font-weight:600;text-align:right;">Tax</th>'
            . '<th style="padding:8px 10px;font-size:12px;color:#6b7280;font-weight:600;text-align:right;">Subtotal</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '<tfoot>' . $summaryRows . '</tfoot>'
            . '</table>';
    }

    protected function orderRecipient(Order $order): ?string
    {
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];

        return $shippingAddress['email']
            ?? $order->customer?->email;
    }

    protected function orderLocale(Order $order): ?string
    {
        $order->loadMissing('customer');
        return filled($order->customer?->language) ? $order->customer->language : null;
    }

    protected function resolvedShippingStatusLabel(Order $order): string
    {
        return match ($order->status) {
            OrderStatus::Pending => OrderShippingStatus::Pending->label(),
            OrderStatus::Processing => OrderShippingStatus::InDelivery->label(),
            OrderStatus::Shipped => OrderShippingStatus::Shipped->label(),
            OrderStatus::Delivered, OrderStatus::Completed => OrderShippingStatus::Delivered->label(),
            OrderStatus::Cancelled, OrderStatus::Rejected => OrderShippingStatus::Cancelled->label(),
        };
    }

    // ── Admin (central) notification methods ────────────────────────────────

    public function sendAdminOrderAlert(Order $order): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminOrderAlert,
            $adminEmail,
            $this->orderTokensForCentral($order),
        );
    }

    public function sendAdminShippingEscalation(Order $order, OrderShippingStatus $shippingStatus): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminShippingEscalation,
            $adminEmail,
            $this->orderTokensForCentral($order, [
                '{{shipping_status}}' => $shippingStatus->label(),
                '{{escalation_reason}}' => 'Delivery cancelled for order ' . $order->uuid,
                '{{assigned_team}}' => 'Central Operations',
            ]),
        );
    }

    public function sendAdminVendorWalletTransaction(Tenant $tenant, Transaction $transaction): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminVendorWalletTransaction,
            $adminEmail,
            $this->transactionTokensForAdmin($tenant, $transaction),
        );
    }

    public function sendAdminSubscriptionActivated(Tenant $tenant, Package $package, PaymentLog $log): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminTenantSubscriptionActivated,
            $adminEmail,
            $this->subscriptionTokensForAdmin($tenant, $package, $log, 'active'),
        );
    }

    public function sendAdminSubscriptionCancelled(Tenant $tenant, Package $package, PaymentLog $log): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminTenantSubscriptionCancelled,
            $adminEmail,
            $this->subscriptionTokensForAdmin($tenant, $package, $log, 'cancelled'),
        );
    }

    public function sendAdminSubscriptionRenewalReminder(Tenant $tenant, Package $package, PaymentLog $log): bool
    {
        $adminEmail = $this->centralAdminEmail();

        if (!$adminEmail) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::AdminTenantSubscriptionRenewalReminder,
            $adminEmail,
            $this->subscriptionTokensForAdmin($tenant, $package, $log, 'renewal_due'),
        );
    }

    // ── Tenant (vendor) notification methods ────────────────────────────────

    public function sendTenantSubscriptionActivated(Tenant $tenant, Package $package, PaymentLog $log, ?string $locale = null): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::TenantSubscriptionActivated,
            $recipient,
            $this->subscriptionTokensForTenant($tenant, $package, $log, 'active'),
            [],
            $locale,
        );
    }

    public function sendTenantSubscriptionRenewalReminder(Tenant $tenant, Package $package, PaymentLog $log): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        $tokens = $this->subscriptionTokensForTenant($tenant, $package, $log, 'renewal_due');

        $config = $this->configurationResolver->central();

        $template = $this->resolveCentralTemplate(EmailTemplateAction::TenantSubscriptionRenewalReminder);

        if (!$template) {
            return false;
        }

        if ($this->tryDeliver(
            $config,
            $recipient,
            $this->render($template->subject, array_replace($this->commonTokens($config), $tokens)),
            $this->render($template->body ?? '', array_replace($this->commonTokens($config), $tokens)),
        )) {
            return true;
        }

        $this->notifyAdminOfDeliveryFailure(EmailTemplateAction::TenantSubscriptionRenewalReminder, $recipient);

        return false;
    }

    public function sendTenantRefundProcessed(Order $order): bool
    {
        return $this->sendTenantTemplate(
            EmailTemplateAction::TenantRefundProcessed,
            $this->orderRecipient($order),
            $this->orderTokens($order, [
                '{{transaction_status}}' => 'Refunded',
                '{{processed_at}}' => optional($order->updated_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
                '{{cancellation_reason}}' => 'Order cancelled and refund issued.',
            ]),
            [],
            $this->orderLocale($order),
        );
    }

    public function sendTenantWelcome(Tenant $tenant, string $storeUrl, string $adminLoginUrl, ?string $subdomainUrl = null, ?string $locale = null): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::CentralTenantWelcome,
            $recipient,
            [
                '{{tenant_name}}' => $tenant->name ?: $tenant->slug,
                '{{tenant_email}}' => $tenant->email ?? '-',
                '{{store_url}}' => $storeUrl,
                '{{admin_login_url}}' => $adminLoginUrl,
                '{{subdomain_url}}' => $subdomainUrl ?? $storeUrl,
                '{{has_custom_domain}}' => $subdomainUrl ? 'true' : 'false',
            ],
            [],
            $locale,
        );
    }

    public function sendRegistrationCompleteLink(string $email, string $planName, string $completeUrl, string $expiresAt, ?string $locale = null): bool
    {
        if (!filled($email)) {
            return false;
        }

        return $this->sendCentralTemplate(
            EmailTemplateAction::CentralRegistrationCompleteLink,
            $email,
            [
                '{{registration_email}}' => $email,
                '{{registration_plan}}' => $planName,
                '{{registration_complete_url}}' => $completeUrl,
                '{{registration_expires_at}}' => $expiresAt,
            ],
            [],
            $locale,
        );
    }

    public function sendTenantWalletTransactionReceipt(Transaction $transaction, Tenant $tenant): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        return $this->sendTenantTemplate(
            EmailTemplateAction::TenantWalletTransactionReceipt,
            $recipient,
            $this->transactionTokensForTenant($transaction),
        );
    }

    /**
     * Notify a vendor/tenant that their sales have crossed the gateway warning threshold.
     * Sends to the tenant's email address using the central mail configuration.
     */
    public function sendGatewayLimitWarning(Tenant $tenant, float $salesAmount, float $limitAmount): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        $config = $this->configurationResolver->central();

        $template = $this->onCentral(fn() => $this->resolveCentralTemplate(EmailTemplateAction::AdminGatewayLimitWarning));

        if (!$template) {
            return false;
        }

        $tokens = array_replace(
            $this->commonTokens($config),
            $this->gatewayLimitTokens($tenant, $salesAmount, ['{{limit_amount}}' => number_format($limitAmount, 2)]),
        );

        if ($this->tryDeliver(
            $config,
            $recipient,
            $this->render($template->subject, $tokens),
            $this->render($template->body ?? '', $tokens),
        )) {
            return true;
        }

        $this->notifyAdminOfDeliveryFailure(EmailTemplateAction::AdminGatewayLimitWarning, $recipient);

        return false;
    }

    /**
     * Notify a vendor/tenant that their store has been blocked due to exceeding
     * the gateway block threshold and they must configure their own payment gateway.
     */
    public function sendGatewayLimitBlock(Tenant $tenant, float $salesAmount, float $blockAmount): bool
    {
        $recipient = $tenant->email;

        if (!filled($recipient)) {
            return false;
        }

        $config = $this->configurationResolver->central();

        $template = $this->onCentral(fn() => $this->resolveCentralTemplate(EmailTemplateAction::AdminGatewayLimitBlock));

        if (!$template) {
            return false;
        }

        $tokens = array_replace(
            $this->commonTokens($config),
            $this->gatewayLimitTokens($tenant, $salesAmount, ['{{block_amount}}' => number_format($blockAmount, 2)]),
        );

        if ($this->tryDeliver(
            $config,
            $recipient,
            $this->render($template->subject, $tokens),
            $this->render($template->body ?? '', $tokens),
        )) {
            return true;
        }

        $this->notifyAdminOfDeliveryFailure(EmailTemplateAction::AdminGatewayLimitBlock, $recipient);

        return false;
    }

    protected function centralAdminEmail(): ?string
    {
        $config = $this->configurationResolver->central();
        return filled($config['from_address']) ? $config['from_address'] : null;
    }

    protected function orderTokensForCentral(Order $order, array $overrides = []): array
    {
        $currentTenant = $order->getConnection()->getDatabaseName();
        $tenantModel = $this->onCentral(fn() => Tenant::query()->get()
            ->first(fn(Tenant $t) => tenancy()->central(fn() => true) || true));

        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $paymentDetails = is_array($order->payment_details) ? $order->payment_details : [];

        $tokens = [
            '{{order_number}}' => $order->uuid,
            '{{store_name}}' => tenant()?->data['shop_name'] ?? tenant()?->name ?? '-',
            '{{customer_name}}' => $shippingAddress['name'] ?? $order->customer?->full_name ?? 'Customer',
            '{{order_status}}' => $order->status->label(),
            '{{shipping_status}}' => $this->resolvedShippingStatusLabel($order),
            '{{order_total}}' => number_format((float) $order->grand_total, 2),
            '{{payment_method}}' => $order->paymentGateway?->name ?? $order->payment_method ?? '-',
            '{{placed_at}}' => optional($order->created_at)->format('M d, Y H:i') ?: '-',
            '{{action_summary}}' => 'New order placed — requires review.',
            '{{admin_note}}' => '-',
            '{{payment_reference}}' => $paymentDetails['transaction_id'] ?? '-',
        ];

        return array_replace($tokens, $overrides);
    }

    protected function transactionTokensForAdmin(Tenant $tenant, Transaction $transaction): array
    {
        return [
            '{{transaction_number}}' => $transaction->uuid,
            '{{tenant_name}}' => $tenant->data['shop_name'] ?? $tenant->name ?? '-',
            '{{tenant_email}}' => $tenant->email ?? '-',
            '{{tenant_domain}}' => $tenant->domains()->first()?->domain ?? '-',
            '{{transaction_type}}' => $transaction->type->label(),
            '{{transaction_amount}}' => number_format((float) $transaction->amount, 2),
            '{{transaction_status}}' => 'Recorded',
            '{{wallet_balance}}' => '-',
            '{{processed_at}}' => optional($transaction->created_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            '{{payment_reference}}' => $transaction->uuid,
            '{{finance_note}}' => $transaction->description ?? '-',
            '{{admin_name}}' => 'System',
        ];
    }

    protected function transactionTokensForTenant(Transaction $transaction): array
    {
        return [
            '{{transaction_number}}' => $transaction->uuid,
            '{{transaction_type}}' => $transaction->type->label(),
            '{{transaction_amount}}' => number_format((float) $transaction->amount, 2),
            '{{transaction_status}}' => 'Recorded',
            '{{wallet_balance}}' => '-',
            '{{processed_at}}' => optional($transaction->created_at)->format('M d, Y H:i') ?: now()->format('M d, Y H:i'),
            '{{payment_reference}}' => $transaction->uuid,
            '{{customer_name}}' => tenant()?->data['shop_name'] ?? tenant()?->name ?? '-',
        ];
    }

    protected function subscriptionTokensForAdmin(Tenant $tenant, Package $package, PaymentLog $log, string $status): array
    {
        $term = $package->term?->value ?? 'monthly';
        $startsAt = $log->paid_at ?? now();
        $endsAt = match ($term) {
            'quarterly' => $startsAt->copy()->addMonths(3),
            'yearly' => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };

        return [
            '{{tenant_name}}' => $tenant->data['shop_name'] ?? $tenant->name ?? '-',
            '{{tenant_email}}' => $tenant->email ?? '-',
            '{{tenant_domain}}' => $tenant->domains()->first()?->domain ?? '-',
            '{{package_name}}' => $package->name ?? '-',
            '{{term}}' => ucfirst($term),
            '{{starts_at}}' => $startsAt->format('M d, Y'),
            '{{ends_at}}' => $endsAt->format('M d, Y'),
            '{{renewal_amount}}' => number_format((float) $package->price, 2),
            '{{subscription_status}}' => ucfirst($status),
            '{{payment_reference}}' => $log->reference ?? '-',
            '{{transaction_status}}' => ucfirst($log->status->value),
            '{{processed_at}}' => ($log->paid_at ?? now())->format('M d, Y H:i'),
            '{{admin_name}}' => 'Central Admin',
            '{{cancellation_reason}}' => $status === 'cancelled' ? 'Payment failed or subscription removed.' : '-',
            '{{retry_count}}' => '0',
            '{{renewal_note}}' => '-',
        ];
    }

    protected function subscriptionTokensForTenant(Tenant $tenant, Package $package, PaymentLog $log, string $status): array
    {
        $term = $package->term?->value ?? 'monthly';
        $startsAt = $log->paid_at ?? now();
        $endsAt = match ($term) {
            'quarterly' => $startsAt->copy()->addMonths(3),
            'yearly' => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };

        return [
            '{{tenant_name}}' => $tenant->data['shop_name'] ?? $tenant->name ?? '-',
            '{{tenant_email}}' => $tenant->email ?? '-',
            '{{tenant_domain}}' => $tenant->domains()->first()?->domain ?? '-',
            '{{package_name}}' => $package->name ?? '-',
            '{{term}}' => ucfirst($term),
            '{{starts_at}}' => $startsAt->format('M d, Y'),
            '{{ends_at}}' => $endsAt->format('M d, Y'),
            '{{renewal_amount}}' => number_format((float) $package->price, 2),
            '{{subscription_status}}' => ucfirst($status),
            '{{payment_reference}}' => $log->reference ?? '-',
        ];
    }

    protected function gatewayLimitTokens(Tenant $tenant, float $salesAmount, array $extra = []): array
    {
        return array_replace([
            '{{tenant_name}}' => $tenant->data['shop_name'] ?? $tenant->name ?? '-',
            '{{tenant_email}}' => $tenant->email ?? '-',
            '{{tenant_domain}}' => $this->onCentral(fn() => $tenant->domains()->first()?->domain ?? '-'),
            '{{sales_amount}}' => number_format($salesAmount, 2),
        ], $extra);
    }

    protected function onCentral(callable $callback): mixed
    {
        if (tenant()) {
            return tenancy()->central($callback);
        }

        return $callback();
    }
}
