<?php

namespace App\Livewire\Website;

use App\Jobs\SyncFixedShippingCostsToTenantsJob;
use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\Category;
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

    // State flags
    public bool $shopNameTaken = false;
    public bool $invalid = false;
    public bool $registered = false;
    public bool $hasDomainRequest = false;
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

        $this->loadDnsRecords();
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

        $this->shopNameTaken = Tenant::query()->where('slug', $slug)->exists()
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
     * Step 1 – validate shop details.
     * If the plan requires category selection, advance to step 2.
     * Otherwise, finalize the registration immediately.
     */
    public function submitStep1(WebsiteRegistrationService $registrationService): void
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

        if ($this->categoriesCount > 0) {
            $this->step = 2;
            return;
        }

        $this->finalize($registrationService, []);
    }

    /**
     * Step 2 – validate category selection, then finalize.
     */
    public function submitStep2(WebsiteRegistrationService $registrationService): void
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

        $this->finalize($registrationService, array_map('intval', $this->selectedCategoryIds));
    }

    public function prevStep(): void
    {
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

    protected function finalize(WebsiteRegistrationService $registrationService, array $categoryIds): void
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

        $registrationData = [
            'name' => $this->name,
            'email' => $pending->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'phone' => $pending->phone ?? $this->phone,
            'shop_name' => $this->shopName,
            'category_ids' => !empty($categoryIds) ? $categoryIds : null,
            'profit_percentage' => (float) ($this->profitPercentage ?: 0),
            'domain_type' => $this->domainType,
            'custom_domain' => $this->customDomain,
            'package_id' => $pending->package_id,
            'gateway_code' => $pending->payment_data['gateway_code'] ?? null,
            'locale' => $pending->locale,
        ];

        $payment = !empty($pending->payment_data) ? $pending->payment_data : null;

        $tenant = $registrationService->finalize($registrationData, $payment);

        $pending->markCompleted();

        SyncProductFixedShippingCosts::dispatch();


        $this->tenantDomain = (string) ($tenant->domains->first()?->domain ?? '');
        $this->customDomain = $this->domainType === 'custom'
            ? $registrationService->normalizeDomain($this->customDomain)
            : '';
        $this->hasDomainRequest = $this->domainType === 'custom';
        $this->registered = true;
        $this->dispatch('scrollToTop');
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

        return view('livewire.website.complete-registration', [
            'centralDomain' => $this->centralDomain,
            'rootCategories' => $rootCategories,
            'requiredDnsRecords' => $requiredDnsRecords,
        ])->layout('layouts.website', ['title' => __('Complete Your Registration') . ' — Ecommet']);
    }
}
