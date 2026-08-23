<?php

namespace App\Livewire\Website;

use App\Jobs\SyncFixedShippingCostsToTenantsJob;
use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\Category;
use App\Models\Country;
use App\Models\DnsRecord;
use App\Models\DomainRequest;
use App\Models\PendingRegistration;
use App\Models\Tenant;
use App\Models\TenantOwner;
use App\Services\DnsRecordService;
use App\Services\WebsiteRegistrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class CompleteRegistrationPage extends Component
{
    #[Url]
    public string $token = '';

    // Readonly info (from pending registration)
    public string $email = '';
    public string $phone = '';
    public string $planName = '';

    // Multi-step state: 1 = shop setup, 2 = category selection
    public int $step = 1;

    // How many root categories this plan allows (0 = unlimited / skip step 2)
    public int $categoriesCount = 0;

    // Step 1 – Shop setup fields
    public string $name = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $shopName = '';
    public string $domainType = 'subdomain';
    public string $customDomain = '';
    public string $profitPercentage = '0';

    // Step 2 – Category selection
    public array $selectedCategoryIds = [];
    public array $categoryPreviewTree = [];

    // Step 3 – Target countries selection
    public array $selectedCountryIds = [];
    public string $countrySearch = '';

    // State flags
    public bool $shopNameTaken = false;
    public bool $invalid = false;
    public bool $registered = false;
    public bool $hasDomainRequest = false;
    public string $registeredTenantId = '';
    public string $tenantDomain = '';
    public string $centralDomain = '';

    // DNS check state (shown after registration when custom domain chosen)
    public bool $dnsConnected = false;
    public bool $dnsChecking = false;
    public array $dnsCheckResults = []; // [['type','name','value','ok'], ...]

    // DNS records to display to user before submitting (step 1, custom domain)
    public array $dnsRecordsForDisplay = [];

    public function mount(): void
    {
        $registrationService = app(WebsiteRegistrationService::class);
        $this->centralDomain = $registrationService->centralDomain();

        if (blank($this->token)) {
            $this->invalid = true;
            return;
        }

        $pending = PendingRegistration::query()
            ->with('package')
            ->where('token', $this->token)
            ->first();

        if (!$pending) {
            $this->invalid = true;
            return;
        }

        // Already completed — restore the success state so the user can check DNS
        if ($pending->isCompleted()) {
            $tenant = Tenant::query()
                ->with('domains')
                ->whereJsonContains('data->email', $pending->email)
                ->latest('created_at')
                ->first();

            if (!$tenant) {
                $this->invalid = true;
                return;
            }

            $domainRequest = DomainRequest::query()
                ->where('tenant_id', $tenant->id)
                ->latest('id')
                ->first();

            $this->tenantDomain = (string) ($tenant->domains->first()?->domain ?? '');
            $this->customDomain = $domainRequest?->domain ?? '';
            $this->hasDomainRequest = $domainRequest !== null;
            $this->registered = true;
            return;
        }

        if ($pending->isExpired()) {
            $this->invalid = true;
            return;
        }

        $this->email = $pending->email;
        $this->phone = (string) ($pending->phone ?? '');
        $package = $pending->package;
        $this->planName = $package?->name ?? __('Free Plan');
        $this->categoriesCount = (int) ($package?->categories_count ?? 0);

        if (!empty($pending->category_ids)) {
            $this->selectedCategoryIds = array_map('strval', (array) $pending->category_ids);
        }

        if (!empty($pending->country_ids)) {
            $this->selectedCountryIds = array_map('strval', (array) $pending->country_ids);
        } else {
            $this->selectedCountryIds = Country::query()
                ->where('is_active_for_tenants', true)
                ->where('is_free', true)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        $this->loadDnsRecords();
        $this->buildCategoryPreview();

        $oauth = session('website.register.oauth');

        if (is_array($oauth) && ($oauth['email'] ?? null) === $this->email) {
            $this->name = (string) ($oauth['name'] ?? '');
        }
    }

    public function updatedDomainType(): void
    {
        $this->checkShopNameAvailability();
        $this->loadDnsRecords();
    }

    public function updatedShopName(): void
    {
        $this->checkShopNameAvailability();
    }

    public function updatedSelectedCategoryIds(): void
    {
        $this->buildCategoryPreview();
    }

    public function buildCategoryPreview(): void
    {
        if (empty($this->selectedCategoryIds)) {
            $this->categoryPreviewTree = [];
            return;
        }

        $rootIds = array_map('intval', $this->selectedCategoryIds);

        $allIds = $rootIds;
        $check = $rootIds;

        while (!empty($check)) {
            $children = Category::whereIn('parent_id', $check)
                ->where('status', 'published')
                ->pluck('id')
                ->toArray();
            $new = array_diff($children, $allIds);
            $allIds = array_merge($allIds, $new);
            $check = $new;
        }

        $all = Category::query()
            ->with('translations.language')
            ->whereIn('id', $allIds)
            ->orderBy('parent_id')
            ->orderBy('order_number')
            ->get()
            ->keyBy('id');

        $tree = [];
        foreach ($all as $cat) {
            if (in_array($cat->id, $rootIds, true)) {
                $tree[$cat->id] = [
                    'id' => $cat->id,
                    'name' => $cat->translationValue('name') ?: (string) $cat->id,
                    'children' => [],
                ];
            }
        }

        foreach ($all as $cat) {
            if ($cat->parent_id && isset($tree[$cat->parent_id])) {
                $tree[$cat->parent_id]['children'][] = [
                    'id' => $cat->id,
                    'name' => $cat->translationValue('name') ?: (string) $cat->id,
                    'children' => $this->buildSubTree($cat->id, $all, $rootIds),
                ];
            }
        }

        $this->categoryPreviewTree = array_values($tree);
    }

    private function buildSubTree(int $parentId, $all, array $rootIds): array
    {
        $children = [];
        foreach ($all as $cat) {
            if ($cat->parent_id === $parentId && !in_array($cat->id, $rootIds, true)) {
                $children[] = [
                    'id' => $cat->id,
                    'name' => $cat->translationValue('name') ?: (string) $cat->id,
                    'children' => $this->buildSubTree($cat->id, $all, $rootIds),
                ];
            }
        }
        return $children;
    }

    private function checkShopNameAvailability(): void
    {
        if ($this->domainType !== 'subdomain' || blank($this->shopName)) {
            $this->shopNameTaken = false;
            return;
        }

        $slug = Str::slug($this->shopName);

        if (blank($slug)) {
            $this->shopNameTaken = false;
            return;
        }

        $this->shopNameTaken = Tenant::query()->where('data->slug', $slug)->exists()
            || DB::table('domains')->where('domain', 'like', $slug . '.%')->exists();
    }

    private function loadDnsRecords(): void
    {
        $records = DnsRecord::query()->where('is_required', true)->orderBy('type')->orderBy('name')->get();
        $this->dnsRecordsForDisplay = $records->map(fn($r) => [
            'type' => $r->type,
            'name' => $r->name,
            'value' => $r->value,
            'ttl' => $r->ttl,
            'priority' => $r->priority,
            'description' => $r->description,
        ])->all();
    }

    /**
     * Step 1 – validate shop details, then advance to category selection
     * (if the plan requires it) or straight to the target countries step.
     */
    public function submitStep1(): void
    {

        $this->checkShopNameAvailability();

        if ($this->shopNameTaken) {
            $this->addError('shopName', __('This subdomain is already taken. Please choose a different shop name or use your own domain.'));
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'same:password'],
            'shopName' => ['required', 'string', 'max:255'],
            'domainType' => ['required', Rule::in(['subdomain', 'custom'])],
            'customDomain' => $this->domainType === 'custom'
                ? [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                    Rule::unique('domain_requests', 'domain'),
                    Rule::unique('domains', 'domain'),
                ]
                : ['nullable', 'string'],
            'profitPercentage' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);

        $this->step = $this->categoriesCount > 0 ? 2 : 3;
    }

    /**
     * Step 2 – validate category selection, advance to the countries step.
     */
    public function submitStep2(): void
    {
        $this->validate([
            'selectedCategoryIds' => [
                'required',
                'array',
                'min:1',
                "max:{$this->categoriesCount}",
            ],
            'selectedCategoryIds.*' => ['integer', Rule::exists('categories', 'id')],
        ]);

        $this->step = 3;
    }

    /**
     * Step 3 – validate target countries selection, then finalize.
     */
    public function submitStep3(WebsiteRegistrationService $registrationService): void
    {
        $this->validate([
            'selectedCountryIds' => ['required', 'array', 'min:1'],
            'selectedCountryIds.*' => [
                'integer',
                Rule::exists('countries', 'id')->where('is_active_for_tenants', true),
            ],
        ]);

        $this->finalize(
            $registrationService,
            array_map('intval', $this->selectedCategoryIds),
            array_map('intval', $this->selectedCountryIds)
        );
    }

    public function prevStep(): void
    {
        if ($this->step === 3) {
            $this->step = $this->categoriesCount > 0 ? 2 : 1;
            return;
        }

        $this->step = 1;
    }

    /**
     * Check DNS connectivity for the custom domain after registration.
     */
    public function checkDns(DnsRecordService $service): void
    {
        if (!$this->hasDomainRequest || blank($this->customDomain)) {
            return;
        }

        $this->dnsChecking = true;
        $this->dnsCheckResults = [];

        $records = DnsRecord::query()->where('is_required', true)->get();

        if ($records->isEmpty()) {
            $this->dnsConnected = true;
            $this->dnsChecking = false;
            return;
        }

        $result = $service->checkDomain($this->customDomain, $records);

        $this->dnsConnected = $result['connected'];
        $this->dnsCheckResults = collect($result['checks'])->map(fn($c) => [
            'type' => $c['record']->type,
            'name' => $c['record']->name,
            'value' => $c['record']->value,
            'ok' => $c['ok'],
        ])->all();

        $this->dnsChecking = false;
    }

    protected function finalize(WebsiteRegistrationService $registrationService, array $categoryIds, array $countryIds = []): void
    {
        $pending = PendingRegistration::query()
            ->where('token', $this->token)
            ->first();

        if (!$pending || !$pending->isValid()) {
            $this->invalid = true;
            return;
        }

        if (!empty($categoryIds)) {
            $pending->update(['category_ids' => $categoryIds]);
        }

        if (!empty($countryIds)) {
            $pending->update(['country_ids' => $countryIds]);
        }

        $registrationData = [
            'name' => $this->name,
            'email' => $pending->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'phone' => $pending->phone ?? $this->phone,
            'shop_name' => $this->shopName,
            'category_ids' => !empty($categoryIds) ? $categoryIds : null,
            'country_ids' => !empty($countryIds) ? $countryIds : null,
            'profit_percentage' => (float) ($this->profitPercentage ?: 0),
            'domain_type' => $this->domainType,
            'custom_domain' => $this->customDomain,
            'package_id' => $pending->package_id,
            'gateway_code' => $pending->payment_data['gateway_code'] ?? null,
            'locale' => $pending->locale,
        ];

        $oauth = session('website.register.oauth');

        if (is_array($oauth) && ($oauth['email'] ?? null) === $pending->email) {
            $registrationData['provider'] = $oauth['provider'] ?? null;
            $registrationData['provider_id'] = $oauth['provider_id'] ?? null;
            $registrationData['avatar'] = $oauth['avatar'] ?? null;
        }

        $payment = !empty($pending->payment_data) ? $pending->payment_data : null;

        $tenant = $registrationService->finalize($registrationData, $payment);

        session()->forget('website.register.oauth');

        $pending->markCompleted();

        SyncProductFixedShippingCosts::dispatch();


        $this->tenantDomain = (string) ($tenant->domains->first()?->domain ?? '');
        $this->customDomain = $this->domainType === 'custom'
            ? $registrationService->normalizeDomain($this->customDomain)
            : '';
        $this->hasDomainRequest = $this->domainType === 'custom';
        $this->registered = true;
        $this->dispatch('scrollToTop');

        if (!$this->hasDomainRequest) {
            $this->redirect(route('website.store.onboarding', [
                'tenantId' => $tenant->getTenantKey(),
            ]));
            return;
        }

        $this->registeredTenantId = (string) $tenant->getTenantKey();
    }

    public function continueToOnboarding(): void
    {
        if (blank($this->registeredTenantId)) {
            return;
        }

        $this->redirect(route('website.store.onboarding', [
            'tenantId' => $this->registeredTenantId,
        ]));
    }

    private function pruneEmptyCategories(\Illuminate\Database\Eloquent\Collection $categories): \Illuminate\Support\Collection
    {
        $missing = $categories->filter(fn(Category $c) => !isset($c->products_count));
        if ($missing->isNotEmpty()) {
            $missing->loadCount('products');
        }

        return $categories
            ->map(function (Category $category) {
                if ($category->children->isNotEmpty()) {
                    $category->setRelation('children', $this->pruneEmptyCategories($category->children));
                }
                return $category;
            })
            ->filter(fn(Category $c) => $c->children->isNotEmpty() || ($c->products_count ?? 0) > 0)
            ->values();
    }

    public function render()
    {
        $rootCategories = $this->categoriesCount > 0 && $this->step === 2
            ? $this->pruneEmptyCategories(
                Category::query()
                    ->with([
                        'translations.language',
                        'children' => function ($q) {
                            $q->where('status', 'published');
                        },
                        'children.translations.language',
                        'children.children' => function ($q) {
                            $q->where('status', 'published');
                        },
                        'children.children.translations.language',
                    ])
                    ->withCount('products')
                    ->whereNull('parent_id')
                    ->where('status', 'published')
                    ->orderBy('id')
                    ->get()
            )
            : collect();

        $requiredDnsRecords = $this->registered && $this->hasDomainRequest
            ? DnsRecord::query()->where('is_required', true)->orderBy('type')->orderBy('name')->get()
            : collect();

        $countries = $this->step === 3
            ? Country::query()
                ->where('is_active_for_tenants', true)
                ->orderByDesc('is_free')
                ->orderBy('name')
                ->get()
                ->filter(fn (Country $country) => $this->countrySearch === ''
                    || str_contains(strtolower((string) $country->name), strtolower($this->countrySearch)))
                ->values()
            : collect();

        return view('livewire.website.complete-registration', [
            'centralDomain' => $this->centralDomain,
            'rootCategories' => $rootCategories,
            'countries' => $countries,
            'requiredDnsRecords' => $requiredDnsRecords,
            'categoryPreviewTree' => $this->categoryPreviewTree,
        ])->layout('layouts.website', ['title' => __('Complete Your Registration') . ' — Ecommet']);
    }
}
