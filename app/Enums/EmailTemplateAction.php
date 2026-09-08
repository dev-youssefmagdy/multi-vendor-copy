<?php

namespace App\Enums;

enum EmailTemplateAction: string
{
    case AdminInvoiceIssued = 'admin.invoice_issued';
    case AdminOrderAlert = 'admin.order_alert';
    case AdminShippingEscalation = 'admin.shipping_escalation';
    case AdminVendorWalletTransaction = 'admin.vendor_wallet_transaction';
    case AdminTenantSubscriptionActivated = 'admin.tenant_subscription_activated';
    case AdminTenantSubscriptionRenewalReminder = 'admin.tenant_subscription_renewal_reminder';
    case AdminTenantSubscriptionCancelled = 'admin.tenant_subscription_cancelled';
    case AdminGatewayLimitWarning = 'admin.gateway_limit_warning';
    case AdminGatewayLimitBlock = 'admin.gateway_limit_block';
    case TenantOrderConfirmation = 'tenant.order_confirmation';
    case TenantOrderProcessing = 'tenant.order_processing';
    case TenantShippingUpdate = 'tenant.shipping_update';
    case TenantOrderDelivered = 'tenant.order_delivered';
    case TenantOrderCancelled = 'tenant.order_cancelled';
    case TenantPaymentReceipt = 'tenant.payment_receipt';
    case TenantRefundProcessed = 'tenant.refund_processed';
    case TenantWalletTransactionReceipt = 'tenant.wallet_transaction_receipt';
    case TenantReturnUpdate = 'tenant.return_update';
    case TenantSubscriptionActivated = 'tenant.subscription_activated';
    case TenantSubscriptionRenewalReminder = 'tenant.subscription_renewal_reminder';
    case CentralRegistrationCompleteLink = 'central.registration_complete_link';
    case CentralTenantWelcome = 'central.tenant_welcome';

    public function label(): string
    {
        return match ($this) {
            self::AdminInvoiceIssued => 'Invoice Issued',
            self::AdminOrderAlert => 'Order Alert',
            self::AdminShippingEscalation => 'Shipping Escalation',
            self::AdminVendorWalletTransaction => 'Vendor Wallet Transaction',
            self::AdminTenantSubscriptionActivated => 'Tenant Subscription Activated',
            self::AdminTenantSubscriptionRenewalReminder => 'Tenant Subscription Renewal Reminder',
            self::AdminTenantSubscriptionCancelled => 'Tenant Subscription Cancelled',
            self::AdminGatewayLimitWarning => 'Gateway Limit Warning',
            self::AdminGatewayLimitBlock => 'Gateway Limit Block',
            self::TenantOrderConfirmation => 'Order Confirmation',
            self::TenantOrderProcessing => 'Order Processing',
            self::TenantShippingUpdate => 'Shipping Update',
            self::TenantOrderDelivered => 'Order Delivered',
            self::TenantOrderCancelled => 'Order Cancelled',
            self::TenantPaymentReceipt => 'Payment Receipt',
            self::TenantRefundProcessed => 'Refund Processed',
            self::TenantWalletTransactionReceipt => 'Wallet Transaction Receipt',
            self::TenantReturnUpdate => 'Return Request Update',
            self::TenantSubscriptionActivated => 'Store Subscription Activated',
            self::TenantSubscriptionRenewalReminder => 'Store Subscription Renewal Reminder',
            self::CentralRegistrationCompleteLink => 'Registration Complete Link',
            self::CentralTenantWelcome => 'Tenant Welcome',
        };
    }

    public function templateName(): string
    {
        return match ($this) {
            self::AdminInvoiceIssued => 'Order Invoice',
            self::AdminOrderAlert => 'Admin Order Alert',
            self::AdminShippingEscalation => 'Shipping Escalation Notice',
            self::AdminVendorWalletTransaction => 'Vendor Wallet Transaction Alert',
            self::AdminTenantSubscriptionActivated => 'Tenant Subscription Activated',
            self::AdminTenantSubscriptionRenewalReminder => 'Tenant Subscription Renewal Reminder',
            self::AdminTenantSubscriptionCancelled => 'Tenant Subscription Cancelled',
            self::AdminGatewayLimitWarning => 'Vendor Gateway Limit Warning',
            self::AdminGatewayLimitBlock => 'Vendor Gateway Limit Blocked',
            self::TenantOrderConfirmation => 'Customer Order Confirmation',
            self::TenantOrderProcessing => 'Order Processing Update',
            self::TenantShippingUpdate => 'Shipping Update',
            self::TenantOrderDelivered => 'Order Delivered Confirmation',
            self::TenantOrderCancelled => 'Order Cancelled Notice',
            self::TenantPaymentReceipt => 'Payment Receipt',
            self::TenantRefundProcessed => 'Refund Processed',
            self::TenantWalletTransactionReceipt => 'Wallet Transaction Receipt',
            self::TenantReturnUpdate => 'Return Request Update',
            self::TenantSubscriptionActivated => 'Store Subscription Activated',
            self::TenantSubscriptionRenewalReminder => 'Store Subscription Renewal Reminder',
            self::CentralRegistrationCompleteLink => 'Complete Your Registration',
            self::CentralTenantWelcome => 'Tenant Welcome',
        };
    }

    public function templateType(): EmailTemplateType
    {
        if (str_starts_with($this->value, 'admin.')) {
            return EmailTemplateType::Admin;
        }
        if (str_starts_with($this->value, 'central.')) {
            return EmailTemplateType::Admin;
        }
        return EmailTemplateType::Tenant;
    }

    public static function optionsForType(EmailTemplateType|string|null $type = null): array
    {
        if (is_string($type) && $type !== '') {
            $type = EmailTemplateType::from($type);
        }

        $options = [];

        foreach (self::cases() as $case) {
            if ($type instanceof EmailTemplateType && $case->templateType() !== $type) {
                continue;
            }

            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function defaultForType(EmailTemplateType|string $type): ?self
    {
        if (is_string($type)) {
            $type = EmailTemplateType::from($type);
        }

        foreach (self::cases() as $case) {
            if ($case->templateType() === $type) {
                return $case;
            }
        }

        return null;
    }
}
