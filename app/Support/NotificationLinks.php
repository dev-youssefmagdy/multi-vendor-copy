<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant\Order;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Resolves the page a notification should open, in the *viewer's* context.
 *
 * URLs are never stored: a notification written while a tenant is initialised
 * would otherwise bake the wrong host into the link. Instead the notification
 * `data` keeps ids (return_request_id, ticket_id, ...) and the link is built
 * here when the bell/list is rendered. A notification may also carry an
 * explicit `route` (+ `route_params`) in its data to override the lookup.
 */
class NotificationLinks
{
    /** @param array<string, mixed>|null $data */
    public static function admin(string $type, ?array $data): ?string
    {
        $data ??= [];

        return self::explicit($data) ?? self::safely(function () use ($type, $data): ?string {
            return match (true) {
                isset($data['return_request_id']) => self::route('admin.orders.returns.show', ['id' => $data['return_request_id']]),
                isset($data['refund_id']) => self::route('admin.orders.refunds.index'),
                isset($data['ticket_id']) => self::route('admin.support.show', ['ticketId' => $data['ticket_id']]),
                isset($data['payout_id']) => self::route('admin.finance.payouts.edit', ['payout' => $data['payout_id']]),
                $type === 'product_request' && isset($data['request_id']) => self::route('admin.product-requests.show', ['requestId' => $data['request_id']]),
                $type === 'manufacturing' && isset($data['request_id']) => self::route('admin.manufacturing.show', ['id' => $data['request_id']]),
                $type === 'brand_request' && isset($data['request_id']) => self::route('admin.brand-requests.show', ['id' => $data['request_id']]),
                isset($data['order_number'], $data['tenant_id']) => self::route('admin.orders.show', ['tenantId' => $data['tenant_id'], 'orderNumber' => $data['order_number']]),
                $type === 'tenant_change_request' => self::route('admin.plans.tenant-change-requests'),
                isset($data['tenant_id'], $data['type']) => self::route('admin.plans.tenant-change-requests'),
                default => self::route(match ($type) {
                    'tenant' => 'admin.plans.users',
                    'order' => 'admin.orders.index',
                    'payment' => 'admin.payment-logs.index',
                    'return' => 'admin.orders.returns.index',
                    'refund' => 'admin.orders.refunds.index',
                    'support_ticket' => 'admin.support.index',
                    'product_request' => 'admin.product-requests.index',
                    'manufacturing' => 'admin.manufacturing.index',
                    'brand_request' => 'admin.brand-requests.index',
                    'domain' => 'admin.domains.requests',
                    'compliance' => 'admin.compliance.index',
                    'customer' => 'admin.customers.index',
                    default => 'admin.notifications.index',
                }),
            };
        });
    }

    /** @param array<string, mixed>|null $data */
    public static function tenant(string $type, ?array $data): ?string
    {
        $data ??= [];

        return self::explicit($data) ?? self::safely(function () use ($type, $data): ?string {
            return match (true) {
                isset($data['return_request_id']) => self::route('tenant.returns.show', ['id' => $data['return_request_id']]),
                isset($data['refund_id']) => self::route('tenant.refunds.index'),
                isset($data['ticket_id']) => self::route('tenant.support.show', ['ticketId' => $data['ticket_id']]),
                isset($data['customer_id']) => self::route('tenant.customers.show', ['customerId' => $data['customer_id']]),
                $type === 'product_request' && isset($data['request_id']) => self::route('tenant.product-requests.show', ['requestId' => $data['request_id']]),
                $type === 'manufacturing' && isset($data['request_id']) => self::route('tenant.manufacturing.show', ['id' => $data['request_id']]),
                $type === 'brand_request' && isset($data['request_id']) => self::route('tenant.brand-requests.show', ['id' => $data['request_id']]),
                isset($data['order_number']) => self::tenantOrder((string) $data['order_number']),
                default => self::route(match ($type) {
                    'order' => 'tenant.orders.index',
                    'payment' => 'tenant.finance.wallet',
                    'return' => 'tenant.returns.index',
                    'refund' => 'tenant.refunds.index',
                    'support_ticket' => 'tenant.support.index',
                    'product_request' => 'tenant.product-requests.index',
                    'manufacturing' => 'tenant.manufacturing.index',
                    'brand_request' => 'tenant.brand-requests.index',
                    'new_catalog_products' => 'tenant.products.index',
                    'customer' => 'tenant.customers.index',
                    'payout' => 'tenant.finance.payouts',
                    'domain' => 'tenant.settings.domains',
                    default => 'tenant.notifications.index',
                }),
            };
        });
    }

    private static function tenantOrder(string $uuid): ?string
    {
        $id = Order::query()->where('uuid', $uuid)->value('id');

        return $id
            ? self::route('tenant.orders.show', ['orderId' => $id])
            : self::route('tenant.orders.index');
    }

    /** @param array<string, mixed> $data */
    private static function explicit(array $data): ?string
    {
        if (empty($data['route'])) {
            return null;
        }

        return self::safely(fn () => self::route((string) $data['route'], (array) ($data['route_params'] ?? [])));
    }

    /** @param array<string, mixed> $params */
    private static function route(string $name, array $params = []): ?string
    {
        return Route::has($name) ? route($name, $params) : null;
    }

    private static function safely(callable $resolve): ?string
    {
        try {
            return $resolve();
        } catch (Throwable) {
            return null;
        }
    }
}
