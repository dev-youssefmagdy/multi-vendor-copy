<?php

namespace App\Services\Admin;

use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Services\Tenant\OrderLifecycleService;
use RuntimeException;

class OrderFulfillmentService
{
    public function __construct(protected OrderLifecycleService $lifecycleService)
    {
    }

    public function updateShippingStatus(string $tenantId, string $orderNumber, OrderShippingStatus $shippingStatus): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if (!$tenant) {
            throw new RuntimeException('Tenant could not be found for this order.');
        }

        $initialized = false;

        try {
            tenancy()->initialize($tenant);
            $initialized = true;

            $order = Order::query()->where('uuid', $orderNumber)->first();

            if (!$order) {
                throw new RuntimeException('Order could not be found in the tenant store.');
            }

            $this->lifecycleService->updateShippingStatus($order, $shippingStatus);
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }

    public function updateOrderStatus(string $tenantId, string $orderNumber, OrderStatus $orderStatus): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if (!$tenant) {
            throw new RuntimeException('Tenant could not be found for this order.');
        }

        $initialized = false;

        try {
            tenancy()->initialize($tenant);
            $initialized = true;

            $order = Order::query()->where('uuid', $orderNumber)->first();

            if (!$order) {
                throw new RuntimeException('Order could not be found in the tenant store.');
            }

            $this->lifecycleService->updateOrderStatus($order, $orderStatus);
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }

    public function updateTrackingNumber(string $tenantId, string $orderNumber, string $trackingNumber): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if (!$tenant) {
            throw new RuntimeException('Tenant could not be found for this order.');
        }

        $initialized = false;

        try {
            tenancy()->initialize($tenant);
            $initialized = true;

            $order = Order::query()->where('uuid', $orderNumber)->first();

            if (!$order) {
                throw new RuntimeException('Order could not be found in the tenant store.');
            }

            $order->update(['tracking_number' => $trackingNumber ?: null]);
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }
}
