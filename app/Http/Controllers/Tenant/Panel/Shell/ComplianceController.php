<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Shell;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Shell\AcceptComplianceRequest;
use App\Models\Tenant\AdminUser;
use App\Services\Tenant\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class ComplianceController extends PanelController
{
    public function __construct(private readonly ComplianceService $compliance)
    {
    }

    public function accept(AcceptComplianceRequest $request): JsonResponse
    {
        $this->compliance->accept();

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        $redirect = $admin && $admin->tour_seen_at === null
            ? route('tenant.onboarding', ['tab' => 'tour'])
            : url()->previous();

        return $this->success('Compliance documents accepted.', redirect: $redirect);
    }

    public function validateAccept(AcceptComplianceRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
