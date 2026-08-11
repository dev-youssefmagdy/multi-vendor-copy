<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\Tenant\Transaction;
use App\Services\Mail\TemplateMailService;

class TenantTransactionObserver
{
    public function __construct(private readonly TemplateMailService $templateMailService)
    {
    }

    public function created(Transaction $transaction): void
    {
        $tenant = tenant();

        if (!$tenant instanceof Tenant) {
            return;
        }

        $this->templateMailService->sendTenantWalletTransactionReceipt($transaction, $tenant);

        $this->templateMailService->sendAdminVendorWalletTransaction($tenant, $transaction);
    }
}
