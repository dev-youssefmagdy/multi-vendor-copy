<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Repositories\OrderRepository;

class OrderReceiptController extends Controller
{
    public function show(string $tenantId, string $orderNumber, OrderRepository $orders)
    {
        $record = $orders->find($tenantId, $orderNumber);
        abort_if(!$record, 404);

        $order = $orders->orderDetail($record);

        /** @var Tenant|null $tenant */
        $tenant = $record->tenant instanceof Tenant ? $record->tenant : Tenant::query()->find($tenantId);

        $tenantInfo = [
            'name' => $order['tenant']['name'] ?? ($tenant?->name ?? 'Store'),
            'email' => $order['tenant']['email'] ?? $tenant?->email,
            'phone' => $tenant?->phone,
            'logo' => $tenant?->logo,
            'domain' => $tenant?->domains?->first()?->domain,
        ];

        return view('admin.order.receipt', [
            'order' => $order,
            'tenant' => $tenantInfo,
        ]);
    }

    public function shippingLabel(string $tenantId, string $orderNumber, OrderRepository $orders)
    {
        $record = $orders->find($tenantId, $orderNumber);
        abort_if(!$record, 404);

        $order = $orders->orderDetail($record);

        /** @var Tenant|null $tenant */
        $tenant = $record->tenant instanceof Tenant ? $record->tenant : Tenant::query()->find($tenantId);

        $tenantInfo = [
            'name' => $order['tenant']['name'] ?? ($tenant?->name ?? 'Store'),
            'email' => $order['tenant']['email'] ?? $tenant?->email,
            'phone' => $tenant?->phone,
            'logo' => $tenant?->logo,
            'domain' => $tenant?->domains?->first()?->domain,
        ];

        return view('admin.order.shipping-label', [
            'order' => $order,
            'tenant' => $tenantInfo,
        ]);
    }
}
