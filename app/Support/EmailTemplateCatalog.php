<?php

namespace App\Support;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateAction;
use App\Enums\EmailTemplateType;

class EmailTemplateCatalog
{
    public static function definitions(): array
    {
        return array_map(fn(array $definition) => array_merge($definition, [
            'action' => self::actionFor($definition['name']),
        ]), [
            self::template(
                'Order Invoice',
                EmailTemplateType::Admin,
                'Invoice {{invoice_number}} for order {{order_number}}',
                '#0f766e',
                'Central Billing',
                'Invoice issued and ready',
                'Marketplace invoice emails with order, payment, and store details.',
                [
                    'Invoice Number' => '{{invoice_number}}',
                    'Order Number' => '{{order_number}}',
                    'Store' => '{{store_name}}',
                    'Amount Paid' => '{{amount}}',
                    'Payment Method' => '{{payment_method}}',
                    'Issued At' => '{{issued_at}}',
                ],
                [
                    'Customer: {{customer_name}}',
                    'Customer email: {{customer_email}}',
                    'Payment reference: {{payment_reference}}',
                    'Support email: {{support_email}}',
                ],
                'Open billing',
                '{{portal_url}}/admin/wallet/invoices'
            ),
            self::template(
                'Admin Order Alert',
                EmailTemplateType::Admin,
                'Order {{order_number}} requires central review',
                '#1d4ed8',
                'Central Orders',
                'Order lifecycle alert',
                'Use for payment review, fulfillment intervention, or operational escalations.',
                [
                    'Order Number' => '{{order_number}}',
                    'Store' => '{{store_name}}',
                    'Customer' => '{{customer_name}}',
                    'Order Status' => '{{order_status}}',
                    'Shipping Status' => '{{shipping_status}}',
                    'Order Total' => '{{order_total}}',
                ],
                [
                    'Placed at: {{placed_at}}',
                    'Payment method: {{payment_method}}',
                    'Action summary: {{action_summary}}',
                    'Admin note: {{admin_note}}',
                ],
                'Open orders',
                '{{portal_url}}/admin/orders'
            ),
            self::template(
                'Shipping Escalation Notice',
                EmailTemplateType::Admin,
                'Shipping escalation for order {{order_number}}',
                '#b45309',
                'Central Shipping',
                'Shipping issue needs attention',
                'Use for delayed delivery, carrier exception, or handoff problems.',
                [
                    'Order Number' => '{{order_number}}',
                    'Carrier' => '{{carrier_name}}',
                    'Tracking Number' => '{{tracking_number}}',
                    'Shipping Status' => '{{shipping_status}}',
                    'Estimated Delivery' => '{{estimated_delivery}}',
                    'Destination' => '{{shipping_address}}',
                ],
                [
                    'Tracking URL: {{tracking_url}}',
                    'Escalation reason: {{escalation_reason}}',
                    'Assigned team: {{assigned_team}}',
                    'Store: {{store_name}}',
                ],
                'Open shipping queue',
                '{{portal_url}}/admin/orders'
            ),
            self::template(
                'Vendor Wallet Transaction Alert',
                EmailTemplateType::Admin,
                'Wallet transaction {{transaction_number}} posted for {{tenant_name}}',
                '#7c3aed',
                'Central Finance',
                'Vendor wallet movement recorded',
                'Use for credits, debits, payouts, settlements, and balance adjustments.',
                [
                    'Transaction Number' => '{{transaction_number}}',
                    'Tenant' => '{{tenant_name}}',
                    'Direction' => '{{transaction_type}}',
                    'Amount' => '{{transaction_amount}}',
                    'Status' => '{{transaction_status}}',
                    'Wallet Balance' => '{{wallet_balance}}',
                ],
                [
                    'Processed at: {{processed_at}}',
                    'Reference: {{payment_reference}}',
                    'Finance note: {{finance_note}}',
                    'Handled by: {{admin_name}}',
                ],
                'Open wallets',
                '{{portal_url}}/admin/wallets'
            ),
            self::template(
                'Tenant Subscription Activated',
                EmailTemplateType::Admin,
                'Subscription activated for {{tenant_name}}',
                '#0f766e',
                'Central Subscriptions',
                'Tenant subscription is active',
                'Use for central package activations and successful billing onboarding.',
                [
                    'Tenant' => '{{tenant_name}}',
                    'Package' => '{{package_name}}',
                    'Term' => '{{term}}',
                    'Starts At' => '{{starts_at}}',
                    'Ends At' => '{{ends_at}}',
                    'Renewal Amount' => '{{renewal_amount}}',
                ],
                [
                    'Tenant email: {{tenant_email}}',
                    'Tenant domain: {{tenant_domain}}',
                    'Payment reference: {{payment_reference}}',
                    'Activated by: {{admin_name}}',
                ],
                'Open subscriptions',
                '{{portal_url}}/admin/plans/subscriptions'
            ),
            self::template(
                'Tenant Subscription Renewal Reminder',
                EmailTemplateType::Admin,
                'Renewal reminder for {{tenant_name}} on {{ends_at}}',
                '#1d4ed8',
                'Central Subscriptions',
                'Upcoming renewal window',
                'Use when a tenant subscription is approaching renewal or retry thresholds.',
                [
                    'Tenant' => '{{tenant_name}}',
                    'Package' => '{{package_name}}',
                    'Renewal Date' => '{{ends_at}}',
                    'Renewal Amount' => '{{renewal_amount}}',
                    'Billing Status' => '{{transaction_status}}',
                    'Subscription Status' => '{{subscription_status}}',
                ],
                [
                    'Retry count: {{retry_count}}',
                    'Renewal note: {{renewal_note}}',
                    'Tenant email: {{tenant_email}}',
                    'Support email: {{support_email}}',
                ],
                'Review renewal queue',
                '{{portal_url}}/admin/plans/subscriptions'
            ),
            self::template(
                'Tenant Subscription Cancelled',
                EmailTemplateType::Admin,
                'Subscription cancelled for {{tenant_name}}',
                '#be123c',
                'Central Subscriptions',
                'Subscription has been cancelled',
                'Use for plan cancellation, failed billing, or package removal notices.',
                [
                    'Tenant' => '{{tenant_name}}',
                    'Package' => '{{package_name}}',
                    'Cancelled At' => '{{processed_at}}',
                    'Effective End Date' => '{{ends_at}}',
                    'Status' => '{{subscription_status}}',
                    'Reason' => '{{cancellation_reason}}',
                ],
                [
                    'Tenant email: {{tenant_email}}',
                    'Tenant domain: {{tenant_domain}}',
                    'Payment reference: {{payment_reference}}',
                    'Handled by: {{admin_name}}',
                ],
                'Open tenants',
                '{{portal_url}}/admin/tenants'
            ),
            self::template(
                'Customer Order Confirmation',
                EmailTemplateType::Tenant,
                'We received your order {{order_number}}',
                '#0f766e',
                'Storefront Orders',
                'Order confirmed',
                'Customer-facing order confirmation with totals, payment method, and delivery details.',
                [
                    'Order Number' => '{{order_number}}',
                    'Store' => '{{store_name}}',
                    'Customer' => '{{customer_name}}',
                    'Order Total' => '{{order_total}}',
                    'Payment Method' => '{{payment_method}}',
                    'Placed At' => '{{placed_at}}',
                ],
                [
                    'Shipping address: {{shipping_address}}',
                    'Order status: {{order_status}}',
                    'Shipping status: {{shipping_status}}',
                    'Invoice: {{invoice_url}}',
                    'Support email: {{support_email}}',
                ],
                'View order',
                '{{portal_url}}/orders/{{order_number}}'
            ),
            self::template(
                'Order Processing Update',
                EmailTemplateType::Tenant,
                'Your order {{order_number}} is now being prepared',
                '#1d4ed8',
                'Storefront Orders',
                'Order is in progress',
                'Use when the order moves into processing after payment confirmation.',
                [
                    'Order Number' => '{{order_number}}',
                    'Customer' => '{{customer_name}}',
                    'Order Status' => '{{order_status}}',
                    'Shipping Status' => '{{shipping_status}}',
                    'Order Total' => '{{order_total}}',
                    'Expected Dispatch' => '{{estimated_delivery}}',
                ],
                [
                    'Payment reference: {{payment_reference}}',
                    'Assigned team: {{assigned_team}}',
                    'Store note: {{action_summary}}',
                ],
                'Check progress',
                '{{portal_url}}/orders/{{order_number}}'
            ),
            self::template(
                'Shipping Update',
                EmailTemplateType::Tenant,
                'Shipping update for order {{order_number}}',
                '#b45309',
                'Storefront Shipping',
                'Your shipment is moving',
                'Use for dispatch, in-transit, delayed delivery, and tracking updates.',
                [
                    'Order Number' => '{{order_number}}',
                    'Carrier' => '{{carrier_name}}',
                    'Tracking Number' => '{{tracking_number}}',
                    'Shipping Status' => '{{shipping_status}}',
                    'Estimated Delivery' => '{{estimated_delivery}}',
                    'Destination' => '{{shipping_address}}',
                ],
                [
                    'Tracking URL: {{tracking_url}}',
                    'Order status: {{order_status}}',
                    'Support email: {{support_email}}',
                ],
                'Track shipment',
                '{{tracking_url}}'
            ),
            self::template(
                'Order Delivered Confirmation',
                EmailTemplateType::Tenant,
                'Order {{order_number}} has been delivered',
                '#0f766e',
                'Storefront Shipping',
                'Delivery completed',
                'Use when the order is marked delivered and the customer should receive a final confirmation.',
                [
                    'Order Number' => '{{order_number}}',
                    'Delivered To' => '{{customer_name}}',
                    'Delivered At' => '{{processed_at}}',
                    'Carrier' => '{{carrier_name}}',
                    'Tracking Number' => '{{tracking_number}}',
                    'Order Total' => '{{order_total}}',
                ],
                [
                    'Delivery address: {{shipping_address}}',
                    'Need help? Contact {{support_email}}',
                    'Review order details at {{portal_url}}',
                ],
                'Review order',
                '{{portal_url}}/orders/{{order_number}}'
            ),
            self::template(
                'Order Cancelled Notice',
                EmailTemplateType::Tenant,
                'Order {{order_number}} has been cancelled',
                '#be123c',
                'Storefront Orders',
                'Order cancelled',
                'Use for customer-requested cancellations, inventory problems, or payment failures.',
                [
                    'Order Number' => '{{order_number}}',
                    'Customer' => '{{customer_name}}',
                    'Cancelled At' => '{{processed_at}}',
                    'Status' => '{{order_status}}',
                    'Refund Status' => '{{transaction_status}}',
                    'Order Total' => '{{order_total}}',
                ],
                [
                    'Reason: {{cancellation_reason}}',
                    'Payment method: {{payment_method}}',
                    'Support email: {{support_email}}',
                ],
                'Contact support',
                '{{portal_url}}/contact'
            ),
            self::template(
                'Payment Receipt',
                EmailTemplateType::Tenant,
                'Payment received for order {{order_number}}',
                '#1d4ed8',
                'Storefront Payments',
                'Payment received successfully',
                'Use after successful payment capture to confirm the billing record for the order.',
                [
                    'Order Number' => '{{order_number}}',
                    'Amount Received' => '{{transaction_amount}}',
                    'Payment Method' => '{{payment_method}}',
                    'Payment Reference' => '{{payment_reference}}',
                    'Processed At' => '{{processed_at}}',
                    'Transaction Status' => '{{transaction_status}}',
                ],
                [
                    'Store: {{store_name}}',
                    'Customer: {{customer_name}}',
                    'Invoice number: {{invoice_number}}',
                    'Invoice: {{invoice_url}}',
                    'Support email: {{support_email}}',
                ],
                'View receipt',
                '{{invoice_url}}'
            ),
            self::template(
                'Refund Processed',
                EmailTemplateType::Tenant,
                'Refund processed for order {{order_number}}',
                '#7c3aed',
                'Storefront Payments',
                'Refund has been issued',
                'Use when a refund has been approved and sent to the original payment source or store wallet.',
                [
                    'Order Number' => '{{order_number}}',
                    'Refund Amount' => '{{transaction_amount}}',
                    'Refund Reference' => '{{payment_reference}}',
                    'Processed At' => '{{processed_at}}',
                    'Refund Status' => '{{transaction_status}}',
                    'Original Payment Method' => '{{payment_method}}',
                ],
                [
                    'Customer: {{customer_name}}',
                    'Reason: {{cancellation_reason}}',
                    'Support email: {{support_email}}',
                ],
                'Open order history',
                '{{portal_url}}/orders/{{order_number}}'
            ),
            self::template(
                'Wallet Transaction Receipt',
                EmailTemplateType::Tenant,
                'Transaction {{transaction_number}} recorded on your account',
                '#374151',
                'Storefront Finance',
                'Transaction recorded',
                'Use for customer wallet credits, debits, loyalty adjustments, and balance notifications.',
                [
                    'Transaction Number' => '{{transaction_number}}',
                    'Type' => '{{transaction_type}}',
                    'Amount' => '{{transaction_amount}}',
                    'Status' => '{{transaction_status}}',
                    'Wallet Balance' => '{{wallet_balance}}',
                    'Processed At' => '{{processed_at}}',
                ],
                [
                    'Customer: {{customer_name}}',
                    'Reference: {{payment_reference}}',
                    'Support email: {{support_email}}',
                ],
                'Open wallet',
                '{{portal_url}}/wallet'
            ),
            self::template(
                'Store Subscription Activated',
                EmailTemplateType::Tenant,
                'Your subscription for {{package_name}} is active',
                '#0f766e',
                'Vendor Billing',
                'Subscription activated',
                'Use for vendor-facing package activation confirmations and billing starts.',
                [
                    'Package' => '{{package_name}}',
                    'Term' => '{{term}}',
                    'Starts At' => '{{starts_at}}',
                    'Ends At' => '{{ends_at}}',
                    'Renewal Amount' => '{{renewal_amount}}',
                    'Status' => '{{subscription_status}}',
                ],
                [
                    'Store name: {{tenant_name}}',
                    'Store email: {{tenant_email}}',
                    'Payment reference: {{payment_reference}}',
                ],
                'Open billing',
                '{{portal_url}}/admin/finance/billing'
            ),
            self::template(
                'Store Subscription Renewal Reminder',
                EmailTemplateType::Tenant,
                'Your subscription renews on {{ends_at}}',
                '#b45309',
                'Vendor Billing',
                'Renewal reminder',
                'Use to remind the vendor owner about an upcoming billing date and renewal amount.',
                [
                    'Package' => '{{package_name}}',
                    'Term' => '{{term}}',
                    'Renewal Date' => '{{ends_at}}',
                    'Renewal Amount' => '{{renewal_amount}}',
                    'Current Status' => '{{subscription_status}}',
                    'Reference' => '{{payment_reference}}',
                ],
                [
                    'Store name: {{tenant_name}}',
                    'Store domain: {{tenant_domain}}',
                    'Billing contact: {{tenant_email}}',
                    'Support email: {{support_email}}',
                ],
                'Review subscription',
                '{{portal_url}}/admin/finance/billing'
            ),
            self::template(
                'Vendor Gateway Limit Warning',
                EmailTemplateType::Admin,
                'Action required: payment gateway limit reached for {{tenant_name}}',
                '#b45309',
                'Payment Gateway Policy',
                'You are approaching your gateway usage limit',
                'Your store has reached the platform sales threshold for using the shared payment gateway. You must now configure your own payment gateway credentials to continue processing payments without interruption.',
                [
                    'Store Name' => '{{tenant_name}}',
                    'Store Domain' => '{{tenant_domain}}',
                    'Total Sales' => '{{sales_amount}}',
                    'Warning Threshold' => '{{limit_amount}}',
                ],
                [
                    'Log in to your vendor panel and navigate to Settings → Payment Gateways.',
                    'Enable "Use own credentials" and fill in your gateway keys.',
                    'Contact support at: {{support_email}} if you need assistance.',
                ],
                'Configure payment gateway',
                '{{portal_url}}/admin/settings/payment-gateways'
            ),
            self::template(
                'Vendor Gateway Limit Blocked',
                EmailTemplateType::Admin,
                'Your store has been blocked from using the shared payment gateway — {{tenant_name}}',
                '#be123c',
                'Payment Gateway Policy',
                'Your store is now blocked',
                'Your store has exceeded the platform sales limit for using the shared payment gateway. Your storefront has been temporarily suspended. To restore access, you must configure your own payment gateway credentials immediately.',
                [
                    'Store Name' => '{{tenant_name}}',
                    'Store Domain' => '{{tenant_domain}}',
                    'Total Sales' => '{{sales_amount}}',
                    'Block Threshold' => '{{block_amount}}',
                ],
                [
                    'Log in to your vendor panel: {{portal_url}}/admin/settings/payment-gateways',
                    'Enable "Use own credentials" and provide your gateway API keys.',
                    'Your storefront will be restored automatically once your credentials are saved.',
                    'For urgent support contact: {{support_email}}',
                ],
                'Restore your store now',
                '{{portal_url}}/admin/settings/payment-gateways'
            ),
            self::template(
                'Complete Your Registration',
                EmailTemplateType::Admin,
                'Complete your store registration on {{app_name}}',
                '#ea580c',
                'Store Registration',
                'You\'re almost there!',
                'Your payment was received. Click the button below to complete your store setup by filling in your store details. This link is valid for 48 hours.',
                [
                    'Email' => '{{registration_email}}',
                    'Plan' => '{{registration_plan}}',
                    'Link Expires' => '{{registration_expires_at}}',
                ],
                [
                    'Complete your details: {{registration_complete_url}}',
                    'If you did not register, ignore this email.',
                    'Support: {{support_email}}',
                ],
                'Complete Registration',
                '{{registration_complete_url}}'
            ),
            self::template(
                'Tenant Welcome',
                EmailTemplateType::Admin,
                'Welcome to {{app_name}} — your store is ready!',
                '#0891b2',
                'Store Ready',
                'Welcome, {{tenant_name}}!',
                'Your store has been created successfully on {{app_name}}. Use the links below to visit your storefront or log in to your vendor panel and start selling.',
                [
                    'Store Name' => '{{tenant_name}}',
                    'Store URL' => '{{store_url}}',
                    'Temporary Subdomain' => '{{subdomain_url}}',
                    'Admin Login' => '{{admin_login_url}}',
                    'Support' => '{{support_email}}',
                ],
                [
                    'Visit your storefront: {{store_url}}',
                    'Log in to your vendor panel: {{admin_login_url}}',
                    'Your temporary subdomain is always available at {{subdomain_url}} — use it while DNS propagates for your custom domain.',
                    'Customise your store from the dashboard — add products, configure payment methods, and set up shipping.',
                    'Contact us at {{support_email}} if you need any help getting started.',
                ],
                'Go to your store',
                '{{store_url}}'
            ),
        ]);
    }

    protected static function template(
        string $name,
        EmailTemplateType $type,
        string $subject,
        string $accent,
        string $eyebrow,
        string $title,
        string $intro,
        array $facts,
        array $bullets,
        string $ctaLabel,
        string $ctaUrl,
    ): array {
        return [
            'name' => $name,
            'type' => $type->value,
            'subject' => $subject,
            'body' => self::body($accent, $eyebrow, $title, $intro, $facts, $bullets, $ctaLabel, $ctaUrl),
            'status' => ActivationStatus::Active->value,
        ];
    }

    protected static function actionFor(string $name): string
    {
        foreach (EmailTemplateAction::cases() as $action) {
            if ($action->templateName() === $name) {
                return $action->value;
            }
        }

        return str($name)->slug('_')->toString();
    }

    protected static function body(
        string $accent,
        string $eyebrow,
        string $title,
        string $intro,
        array $facts,
        array $bullets,
        string $ctaLabel,
        string $ctaUrl,
    ): string {
        $factsHtml = '';

        foreach ($facts as $label => $value) {
            $factsHtml .= '<tr>'
                . '<td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;width:38%;">' . $label . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;font-weight:600;">' . $value . '</td>'
                . '</tr>';
        }

        $bulletsHtml = '';

        foreach ($bullets as $bullet) {
            $bulletsHtml .= '<li style="margin:0 0 10px 0;">' . $bullet . '</li>';
        }

        return '<div style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#111827;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 0;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="680" cellspacing="0" cellpadding="0" style="width:680px;max-width:680px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,0.12);">'
            . '<tr><td style="padding:0;background:linear-gradient(135deg,' . $accent . ' 0%, #111827 100%);">'
            . '<div style="padding:34px 40px 28px 40px;">'
            . '<div style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.78);font-weight:700;">' . $eyebrow . '</div>'
            . '<h1 style="margin:14px 0 12px 0;font-size:30px;line-height:1.2;color:#ffffff;font-weight:700;">' . $title . '</h1>'
            . '<p style="margin:0;font-size:15px;line-height:1.7;color:rgba(255,255,255,0.88);">' . $intro . '</p>'
            . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:30px 40px 18px 40px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb;border-radius:16px;background:#f8fafc;padding:0 24px;">'
            . $factsHtml
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding:8px 40px 8px 40px;">'
            . '<div style="font-size:14px;line-height:1.8;color:#374151;">'
            . '<p style="margin:0 0 14px 0;">Key details included in this template:</p>'
            . '<ul style="padding-left:18px;margin:0;font-size:14px;line-height:1.7;color:#374151;">' . $bulletsHtml . '</ul>'
            . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:24px 40px 22px 40px;">'
            . '<a href="' . $ctaUrl . '" style="display:inline-block;background:' . $accent . ';color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:999px;">' . $ctaLabel . '</a>'
            . '</td></tr>'
            . '<tr><td style="padding:0 40px 34px 40px;">'
            . '<div style="padding-top:18px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.8;color:#6b7280;">'
            . 'This email was generated by {{app_name}}. If you need help, contact {{support_email}} or visit {{portal_url}}. '
            . 'Copyright {{current_year}} {{app_name}}.'
            . '</div>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</div>';
    }
}
