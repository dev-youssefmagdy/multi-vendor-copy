<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Services\Payments\PaymentReadinessService;

class PaymentReadinessPage extends TenantPage
{
    use InteractsWithTenantUi;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.payment-readiness';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Readiness',
            'badge' => 'Settings',
            'description' => 'Check whether your payment setup is ready to accept orders globally.',
        ];
    }

    protected function pageData(): array
    {
        $report = app(PaymentReadinessService::class)->report(tenant());

        return array_merge(parent::pageData(), ['report' => $report]);
    }
}
