<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Onboarding;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Onboarding\SaveLogoRequest;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\OnboardingService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OnboardingController extends PanelController
{
    public function __construct(
        private readonly OnboardingService $service,
    ) {
    }

    public function show(Request $request, string $tab = 'tour'): View
    {
        $tab = in_array($tab, ['tour', 'setup'], true) ? $tab : 'tour';

        // Reaching the setup tab by any route (tab switch, direct link, sidebar)
        // means the forced first-run tour no longer needs to intercept navigation
        // away from this page — see EnsureTenantTourSeen.
        if ($tab === 'setup') {
            $this->service->markTourSeen();
        }

        $steps = $this->service->steps();

        return view('tenant.pages.onboarding.index', [
            'tab' => $tab,
            'steps' => $steps,
            'totalSteps' => count($steps),
            'setupItems' => $this->service->setupItems(),
            'allItemsDone' => $this->service->allItemsDone(),
            'paymentReadinessItems' => $this->service->paymentReadinessItems(),
            'paymentReadinessSkipped' => $this->service->paymentReadinessSkipped(),
            'logo' => $this->service->logoSettings(),
            'logoFonts' => StorefrontRepository::LOGO_FONTS,
            'highlightItem' => $request->query('item'),
        ]);
    }

    public function completeTour(Request $request): JsonResponse
    {
        $this->service->markTourSeen();

        if ($request->boolean('skipped')) {
            return $this->success('Tour skipped.', redirect: route('tenant.onboarding', ['tab' => 'setup']));
        }

        return $this->success('Tour completed.', redirect: route('tenant.onboarding', ['tab' => 'setup']));
    }

    public function saveLogo(SaveLogoRequest $request, TenantPanelService $appearanceService): JsonResponse
    {
        $validated = $request->validated();

        $logoPathAr = null;
        $logoPathEn = null;

        if ($request->hasFile('logo_upload_ar')) {
            $logoPathAr = tenant_asset($request->file('logo_upload_ar')->store('appearances/logos', 'public'));
        }
        if ($request->hasFile('logo_upload_en')) {
            $logoPathEn = tenant_asset($request->file('logo_upload_en')->store('appearances/logos', 'public'));
        }

        $current = $this->service->logoSettings();

        $appearanceService->saveAppearanceSettings([
            'logo_mode' => $validated['logo_mode'],
            'logo_text_ar' => $validated['logo_text_ar'] ?? '',
            'logo_text_en' => $validated['logo_text_en'] ?? '',
            'logo_color' => $validated['logo_color'],
            'logo_bg_color' => $validated['logo_bg_color'],
            'logo_shape' => $validated['logo_shape'],
            'logo_font_ar' => $validated['logo_font_ar'],
            'logo_font_en' => $validated['logo_font_en'],
            'logo_path_ar' => $logoPathAr ?? ($current['logo_path_ar'] ?? ''),
            'logo_path_en' => $logoPathEn ?? ($current['logo_path_en'] ?? ''),
        ]);

        return $this->success('Logo saved successfully.', [
            'setup' => $this->setupProgress(),
        ]);
    }

    public function validateLogo(SaveLogoRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function skipPaymentReadiness(): JsonResponse
    {
        $this->service->skipPaymentReadiness();

        return $this->success('Payment readiness marked as complete.', [
            'setup' => $this->setupProgress(),
        ]);
    }

    public function dismiss(): JsonResponse
    {
        $this->service->dismissSetup();

        return $this->success('Setup complete — your store is ready.', redirect: route('tenant.dashboard'));
    }

    private function setupProgress(): array
    {
        $items = $this->service->setupItems();

        return [
            'items' => $items,
            'all_done' => $this->service->allItemsDone(),
            'done_count' => count(array_filter($items, fn (array $item) => $item['done'])),
            'total_count' => count($items),
        ];
    }
}
