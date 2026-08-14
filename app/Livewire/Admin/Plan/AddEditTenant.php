<?php

namespace App\Livewire\Admin\Plan;

use App\Enums\PaymentLogStatus;
use App\Enums\TenantStatus;
use App\Models\Category;
use App\Models\Language;
use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\Mail\TemplateMailService;
use App\Services\Tenant\TenantPanelService;
use App\Services\TenantService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditTenant extends Component
{
    use WithFileUploads;

    public ?string $tenantId = null;

    // Account tab
    public string $name = '';
    public string $slug = '';
    public string $email = '';
    public ?string $phone = null;
    public string $status = 'onboarding';
    public array $categoryIds = [];
    public ?int $primaryLanguageId = null;
    public ?int $packageId = null;
    public ?string $trialEndsAt = null;
    public ?string $domain = null;

    public float $profitPercentage = 0;

    // Security tab
    public string $password = '';
    public string $passwordConfirmation = '';

    // Logo
    public $logoUpload = null;

    // UI state
    public string $activeTab = 'account';
    public bool $showPackageModal = false;
    public ?int $assignPackageId = null;
    public string $assignGateway = 'manual';
    public ?string $assignAmount = null;
    public string $assignStatus = 'paid';
    public ?string $assignReference = null;
    public ?string $assignPaidAt = null;

    public function mount(?Tenant $tenant = null): void
    {
        if (!$tenant) {
            return;
        }

        $loaded = app(TenantRepository::class)->findForEditor($tenant);
        $this->tenantId = $loaded->id;
        $this->name = $loaded->name ?? '';
        $this->slug = $loaded->slug ?? '';
        $this->email = $loaded->email ?? '';
        $this->phone = $loaded->phone;
        $this->status = $loaded->status->value;
        $this->categoryIds = is_array($loaded->category_ids) ? array_map('strval', $loaded->category_ids) : [];
        $this->primaryLanguageId = $loaded->primary_language_id;
        $this->packageId = $loaded->package_id;
        $this->trialEndsAt = $loaded->trial_ends_at?->format('Y-m-d\TH:i');
        $this->domain = $loaded->domains->first()?->domain;
        $this->profitPercentage = (float) ($loaded->profit_percentage ?? 0);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openAssignPackage(): void
    {
        $this->assignPackageId = null;
        $this->assignGateway = 'manual';
        $this->assignAmount = null;
        $this->assignStatus = 'paid';
        $this->assignReference = null;
        $this->assignPaidAt = null;
        $this->showPackageModal = true;
    }

    public function closeAssignPackage(): void
    {
        $this->showPackageModal = false;
    }

    public function updatedAssignPackageId(?int $value): void
    {
        if ($value) {
            $package = Package::find($value);
            $this->assignAmount = $package ? (string) $package->price : null;
        }
    }

    public function saveAssignPackage(): void
    {
        $this->validate([
            'assignPackageId' => ['required', 'integer', Rule::exists('packages', 'id')],
            'assignGateway' => ['required', 'string', 'max:50'],
            'assignAmount' => ['nullable', 'numeric', 'min:0'],
            'assignStatus' => ['required', Rule::enum(PaymentLogStatus::class)],
            'assignReference' => ['nullable', 'string', 'max:255'],
            'assignPaidAt' => ['nullable', 'date'],
        ]);

        if ($this->tenantId) {
            $package = Package::findOrFail($this->assignPackageId);

            Tenant::query()->findOrFail($this->tenantId)->update(['package_id' => $this->assignPackageId]);

            PaymentLog::create([
                'tenant_id' => $this->tenantId,
                'package_id' => $this->assignPackageId,
                'gateway' => $this->assignGateway,
                'amount' => $this->assignAmount ?? $package->price,
                'status' => $this->assignStatus,
                'reference' => $this->assignReference ?: null,
                'paid_at' => $this->assignStatus === 'paid'
                    ? ($this->assignPaidAt ? \Carbon\Carbon::parse($this->assignPaidAt) : now())
                    : null,
            ]);

            $log = PaymentLog::query()
                ->where('tenant_id', $this->tenantId)
                ->where('package_id', $this->assignPackageId)
                ->latest()
                ->first();

            if ($log) {
                $mailer = app(TemplateMailService::class);
                $tenant = Tenant::query()->findOrFail($this->tenantId);

                if ($this->assignStatus === 'paid') {
                    $mailer->sendAdminSubscriptionActivated($tenant, $package, $log);
                    $mailer->sendTenantSubscriptionActivated($tenant, $package, $log);
                } elseif ($this->assignStatus === 'failed') {
                    $mailer->sendAdminSubscriptionCancelled($tenant, $package, $log);
                }
            }

            $this->packageId = $this->assignPackageId;
        }

        $this->showPackageModal = false;
        session()->flash('status', 'Package assigned and payment recorded.');
    }

    public function save(TenantService $service): mixed
    {
        $this->slug = \Illuminate\Support\Str::slug($this->slug ?: $this->name);

        $centralDomain = collect(config('tenancy.central_domains', []))
            ->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'slug')->ignore($this->tenantId),
                function (string $attribute, mixed $value, \Closure $fail) use ($centralDomain) {
                    if (blank($value) || !$centralDomain) {
                        return;
                    }

                    $exists = \Stancl\Tenancy\Database\Models\Domain::query()
                        ->where('domain', $value . '.' . $centralDomain)
                        ->when($this->tenantId, fn($q) => $q->where('tenant_id', '!=', $this->tenantId))
                        ->exists();

                    if ($exists) {
                        $fail(__('This subdomain is already taken. Please choose a different shop name.'));
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'categoryIds' => ['array'],
            'categoryIds.*' => ['integer', Rule::exists('categories', 'id')],
            'primaryLanguageId' => ['nullable', 'integer', Rule::exists('languages', 'id')],
            'packageId' => ['nullable', 'integer', Rule::exists('packages', 'id')],
            'trialEndsAt' => ['nullable', 'date'],
            'domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                Rule::unique('domains', 'domain')->where(
                    fn($q) => $this->tenantId ? $q->where('tenant_id', '!=', $this->tenantId) : $q
                ),
                Rule::unique('domain_requests', 'domain'),
            ],
            'profitPercentage' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'password' => [$this->tenantId ? 'nullable' : 'required', 'string', 'min:8'],
            'logoUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        $tenant = $service->save([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'category_ids' => !empty($this->categoryIds) ? array_map('intval', $this->categoryIds) : null,
            'primary_language_id' => $validated['primaryLanguageId'] ?? null,
            'package_id' => $validated['packageId'] ?? null,
            'trial_ends_at' => $validated['trialEndsAt'] ?? null,
            'shop_name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'profit_percentage' => $validated['profitPercentage'] ?? 0,
            'password' => $validated['password'] ?? null,
        ], $this->tenantId ? Tenant::query()->findOrFail($this->tenantId) : null);

        // Handle logo upload (edit only – tenant DB must exist before writing settings)
        if ($this->tenantId && $this->logoUpload) {
            tenancy()->initialize($tenant);
            $path = $this->logoUpload->store('appearances/logos', 'public');
            $logoUrl = tenant_asset($path);
            app(TenantPanelService::class)->saveAppearanceSettings([
                'logo_mode' => 'image',
                'logo_path_ar' => $logoUrl,
                'logo_path_en' => $logoUrl,
            ]);
            tenancy()->end();
            $this->logoUpload = null;
        }

        session()->flash('status', $this->tenantId ? 'Tenant updated successfully.' : 'Tenant created successfully.');

        return redirect()->route('admin.plans.users.edit', $tenant);
    }

    public function render()
    {
        $tenant = $this->tenantId ? Tenant::query()->with(['package', 'paymentLogs' => fn($q) => $q->with('package')->latest()])->find($this->tenantId) : null;

        return view('livewire.admin.plan.add-edit-tenant', [
            'pageTitle' => $this->tenantId ? 'Edit Tenant' : 'Add Tenant',
            'statusOptions' => TenantStatus::cases(),
            'rootCategories' => Category::query()
                ->with('translations.language')
                ->whereNull('parent_id')
                ->where('status', 'published')
                ->orderBy('id')
                ->get(),
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get(),
            'packages' => Package::query()->with('translations.language')->get(),
            'currentPackage' => $tenant?->package,
            'billingHistory' => $tenant?->paymentLogs ?? collect(),
            'currentLogoUrl' => $tenant?->logo,
        ]);
    }
}
