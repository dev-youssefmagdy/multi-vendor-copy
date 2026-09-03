<?php

namespace App\Observers;

use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Models\StaticPage;
use App\Models\Template;
use App\Services\Tenant\CentralCatalogTenantSyncService;

class CentralSharedTenantSyncObserver
{
    public function saved(Currency|Language|PaymentGateway|Template|EmailTemplate|StaticPage $model): void
    {
        // Refresh to ensure the job sees the committed row, not a stale in-memory copy.
        $model->refresh();
        app(CentralCatalogTenantSyncService::class)->syncAllTenants($this->sectionsFor($model));
    }

    public function deleted(Currency|Language|PaymentGateway|Template|EmailTemplate|StaticPage $model): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants($this->sectionsFor($model));
    }

    protected function sectionsFor(Currency|Language|PaymentGateway|Template|EmailTemplate|StaticPage $model): array
    {
        return match (true) {
            $model instanceof Currency => ['currencies'],
            $model instanceof Language => ['languages'],
            $model instanceof PaymentGateway => ['payment-gateways'],
            $model instanceof Template => ['themes'],
            $model instanceof EmailTemplate => ['email-templates'],
            $model instanceof StaticPage => ['pages'],
        };
    }
}
