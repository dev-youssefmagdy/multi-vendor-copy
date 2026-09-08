<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\TenantChangeRequestStatus;
use App\Enums\TenantChangeRequestType;
use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Category;
use App\Models\Country;
use App\Models\TenantChangeRequest;
use App\Models\TenantCountry;
use App\Services\AdminNotificationService;

class GeneralSettingsPage extends ContentPage
{
    use InteractsWithTenantUi;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.general-settings';
    }

    public string $profitPercentage = '0';

    public bool $showCountryRequestModal = false;
    public bool $showCategoryRequestModal = false;
    public array $requestedCountryIds = [];
    public array $requestedCategoryIds = [];

    public function mount(): void
    {
        $tenant = tenant();
        $this->profitPercentage = (string) ($tenant->profit_percentage ?? 0);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'General Settings',
            'badge' => 'Settings',
            'description' => 'Store-wide defaults used across the vendor control panel.',
        ];
    }

    protected function pageData(): array
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

        return array_merge(parent::pageData(), [
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

    public function save(): void
    {
        $validated = $this->validate([
            'profitPercentage' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $profitPercentage = round((float) $validated['profitPercentage'], 4);

        $tenant = tenant();
        $tenant->fill(['profit_percentage' => $profitPercentage]);
        $tenant->save();

        ApplyTenantProfitPercentageJob::dispatch($tenant->id);

        $this->toast('General settings updated. Prices are being recalculated for all products and variants.');
    }

    public function openCountryRequestModal(): void
    {
        $this->requestedCountryIds = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->showCountryRequestModal = true;
    }

    public function closeCountryRequestModal(): void
    {
        $this->showCountryRequestModal = false;
    }

    public function openCategoryRequestModal(): void
    {
        $this->requestedCategoryIds = array_map('strval', (array) (tenant()->category_ids ?? []));
        $this->showCategoryRequestModal = true;
    }

    public function closeCategoryRequestModal(): void
    {
        $this->showCategoryRequestModal = false;
    }

    public function submitCountryChangeRequest(): void
    {
        $this->validate([
            'requestedCountryIds' => ['required', 'array', 'min:1'],
        ], [], ['requestedCountryIds' => 'target countries']);

        $tenantId = tenant()->id;

        $hasPending = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Countries)
            ->pending()
            ->exists();

        if ($hasPending) {
            $this->toast('A target countries change request is already pending review.', 'warning');
            $this->closeCountryRequestModal();
            return;
        }

        $currentIds = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $requestedIds = collect($this->requestedCountryIds)->map(fn ($id) => (int) $id)->values()->all();

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

        $this->closeCountryRequestModal();
        $this->toast('Request sent to admin for review.');
    }

    public function submitCategoryChangeRequest(): void
    {
        $this->validate([
            'requestedCategoryIds' => ['required', 'array', 'min:1'],
        ], [], ['requestedCategoryIds' => 'categories']);

        $tenantId = tenant()->id;

        $hasPending = TenantChangeRequest::query()
            ->forTenant($tenantId)
            ->ofType(TenantChangeRequestType::Categories)
            ->pending()
            ->exists();

        if ($hasPending) {
            $this->toast('A categories change request is already pending review.', 'warning');
            $this->closeCategoryRequestModal();
            return;
        }

        $currentIds = array_map('intval', (array) (tenant()->category_ids ?? []));
        $requestedIds = collect($this->requestedCategoryIds)->map(fn ($id) => (int) $id)->values()->all();

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

        $this->closeCategoryRequestModal();
        $this->toast('Request sent to admin for review.');
    }
}
