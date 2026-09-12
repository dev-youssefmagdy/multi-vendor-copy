<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Shell;

use App\Helpers\TenantNavigation;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;

final class SetupProgressController extends PanelController
{
    public function show(): JsonResponse
    {
        return response()->json(TenantNavigation::setupProgress());
    }

    public function pagesReviewed(TenantPanelService $service): JsonResponse
    {
        $service->markDefaultPagesReviewed();

        return $this->success('Default pages marked as reviewed.');
    }
}
