<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Services\Payments\PaymentReadinessService;
use Illuminate\View\View;

final class PaymentReadinessController extends PanelController
{
    public function index(PaymentReadinessService $service): View
    {
        $report = $service->report(tenant());

        $percent = $report['max_score'] > 0 ? (int) round(($report['score'] / $report['max_score']) * 100) : 0;

        return view('tenant.pages.settings.payment-readiness.index', [
            'report' => $report,
            'percent' => $percent,
            'scoreLabel' => $percent >= 80 ? 'Ready' : ($percent >= 50 ? 'Partial' : 'Not Ready'),
        ]);
    }
}
