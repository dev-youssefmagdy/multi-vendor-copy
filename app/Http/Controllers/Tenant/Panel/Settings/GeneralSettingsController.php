<?php

declare(strict_types=1);

// ROUTES:
// Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');
// Route::post('/general/validate', [GeneralSettingsController::class, 'validateUpdate'])->name('general.validate');
// Route::post('/general/country-request', [GeneralSettingsController::class, 'submitCountryRequest'])->name('general.country-request');
// Route::post('/general/country-request/validate', [GeneralSettingsController::class, 'validateCountryRequest'])->name('general.country-request.validate');
// Route::post('/general/category-request', [GeneralSettingsController::class, 'submitCategoryRequest'])->name('general.category-request');
// Route::post('/general/category-request/validate', [GeneralSettingsController::class, 'validateCategoryRequest'])->name('general.category-request.validate');
// (all under the existing tenant.permission:settings.account.manage middleware, next to the kept `general` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\TenantChangeRequestStatus;
use App\Enums\TenantChangeRequestType;
use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveGeneralSettingsRequest;
use App\Http\Requests\Tenant\Panel\Settings\SubmitCategoryChangeRequest;
use App\Http\Requests\Tenant\Panel\Settings\SubmitCountryChangeRequest;
use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Models\Category;
use App\Models\Country;
use App\Models\TenantChangeRequest;
use App\Models\TenantCountry;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class GeneralSettingsController extends PanelController
{
    public function index(): View
    {
        $tenantId = tenant()->id;

        $currentCountryIds = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $currentCategoryIds = array_map('intval', (array) (tenant()->category_ids ?? []));

        $allCountries = Country::query()
            ->where('is_active_for_tenants', true)
            ->orderByDesc('is_free')
            ->orderBy('name')
            ->get();

        $allCategories = Category::query()
            ->whereNull('parent_id')
            ->where('status', 'published')
            ->with('translations.language')
            ->orderBy('order_number')
            ->get();

        $pendingCountryRequest = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Countries)
            ->pending()
            ->first();

        $pendingCategoryRequest = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Categories)
            ->pending()
            ->first();

        return view('tenant.pages.settings.general.index', [
            'groups' => [
                [
                    'title' => 'Pricing',
                    'description' => 'Store-wide defaults used across the vendor control panel.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Profit Percentage', 'model' => 'profit_percentage', 'type' => 'number'],
                    ],
                ],
            ],
            'values' => [
                'profit_percentage' => (string) (tenant()->profit_percentage ?? 0),
            ],
            'currentCountries' => $allCountries->whereIn('id', $currentCountryIds)->values(),
            'currentCategories' => $allCategories->whereIn('id', $currentCategoryIds)->values(),
            'allCountries' => $allCountries,
            'allCategories' => $allCategories,
            'currentCountryIds' => $currentCountryIds,
            'currentCategoryIds' => $currentCategoryIds,
            'pendingCountryRequest' => $pendingCountryRequest,
            'pendingCategoryRequest' => $pendingCategoryRequest,
        ]);
    }

    public function update(SaveGeneralSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $profitPercentage = round((float) $validated['profit_percentage'], 4);

        $tenant = tenant();
        $tenant->fill(['profit_percentage' => $profitPercentage]);
        $tenant->save();

        ApplyTenantProfitPercentageJob::dispatch($tenant->id);

        return $this->success('General settings updated. Prices are being recalculated for all products and variants.');
    }

    public function validateUpdate(SaveGeneralSettingsRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function submitCountryRequest(SubmitCountryChangeRequest $request): JsonResponse
    {
        $tenantId = tenant()->id;

        $hasPending = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Countries)
            ->pending()
            ->exists();

        if ($hasPending) {
            throw new PanelActionException('A target countries change request is already pending review.', 409, toastType: 'warning');
        }

        $currentIds = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $requestedIds = collect($request->validated('requested_country_ids'))->map(fn ($id) => (int) $id)->values()->all();

        TenantChangeRequest::create([
            'tenant_id' => $tenantId,
            'type' => TenantChangeRequestType::Countries,
            'requested_data' => $requestedIds,
            'current_data' => $currentIds,
            'status' => TenantChangeRequestStatus::Pending,
        ]);

        $tenantName = data_get(tenant()->data, 'name', $tenantId);
        app(AdminNotificationService::class)->notify(
            'tenant_change_request',
            'New Target Countries Change Request',
            "Vendor \"{$tenantName}\" requested a change to their target countries.",
            ['tenant_id' => $tenantId, 'type' => TenantChangeRequestType::Countries->value],
        );

        return $this->success('Request sent to admin for review.');
    }

    public function validateCountryRequest(SubmitCountryChangeRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function submitCategoryRequest(SubmitCategoryChangeRequest $request): JsonResponse
    {
        $tenantId = tenant()->id;

        $hasPending = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Categories)
            ->pending()
            ->exists();

        if ($hasPending) {
            throw new PanelActionException('A categories change request is already pending review.', 409, toastType: 'warning');
        }

        $currentIds = array_map('intval', (array) (tenant()->category_ids ?? []));
        $requestedIds = collect($request->validated('requested_category_ids'))->map(fn ($id) => (int) $id)->values()->all();

        TenantChangeRequest::create([
            'tenant_id' => $tenantId,
            'type' => TenantChangeRequestType::Categories,
            'requested_data' => $requestedIds,
            'current_data' => $currentIds,
            'status' => TenantChangeRequestStatus::Pending,
        ]);

        $tenantName = data_get(tenant()->data, 'name', $tenantId);
        app(AdminNotificationService::class)->notify(
            'tenant_change_request',
            'New Categories Change Request',
            "Vendor \"{$tenantName}\" requested a change to their categories.",
            ['tenant_id' => $tenantId, 'type' => TenantChangeRequestType::Categories->value],
        );

        return $this->success('Request sent to admin for review.');
    }

    public function validateCategoryRequest(SubmitCategoryChangeRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
