<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Services\Admin\MarketplaceInvoiceService;
use App\Services\Tenant\OrderLifecycleService;
use App\Services\TenantGatewayLimitService;

class TenantOrderObserver
{
    public function __construct(
        private readonly MarketplaceInvoiceService $invoiceService,
        private readonly OrderLifecycleService $orderLifecycleService,
        private readonly TenantGatewayLimitService $gatewayLimitService,
    ) {
    }

    public function saved(Order $order): void
    {
        if (!$order->paid || !$order->wasChanged('paid')) {
            return;
        }

        $tenant = tenant();

        if (!$tenant instanceof Tenant) {
            return;
        }

        $invoice = $this->invoiceService->issueForOrder($tenant, $order, true);

        if ($invoice && $invoice->invoice_number) {
            $this->orderLifecycleService->recordInvoiceIssued($order->fresh(), (string) $invoice->invoice_number);
        }

        // Check gateway usage limits and send notification / block emails as needed.
        $this->gatewayLimitService->checkAndNotify($tenant);
    }
}
