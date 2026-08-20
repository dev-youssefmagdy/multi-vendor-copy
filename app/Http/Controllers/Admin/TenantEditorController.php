<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\SanitizesPhoneNumber;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\Package;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\Tenant\TenantPanelService;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantEditorController extends Controller
{
    use SanitizesPhoneNumber;

    public function create(): View
    {
        return view('admin.plan.add-edit-tenant', array_merge(
            $this->viewData(),
            ['tenant' => null, 'pageTitle' => 'Add Tenant']
        ));
    }

    public function edit(Tenant $tenant): View
    {
        $loaded   = app(TenantRepository::class)->findForEditor($tenant);
        $rawPhone = (string) ($loaded->phone ?? '');

        return view('admin.plan.add-edit-tenant', array_merge(
            $this->viewData(),
            [
                'tenant'         => $loaded,
                'pageTitle'      => 'Edit Tenant',
                'phone'          => str_contains($rawPhone, 'object') ? '' : $rawPhone,
                'categoryIds'    => is_array($loaded->category_ids) ? array_map('strval', $loaded->category_ids) : [],
                'billingHistory' => $loaded->paymentLogs()->with('package')->latest()->get(),
                'currentLogoUrl' => $loaded->logo,
            ]
        ));
    }

    public function store(Request $request, TenantService $service): RedirectResponse
    {
        return $this->processForm($request, $service, null);
    }

    public function update(Request $request, Tenant $tenant, TenantService $service): RedirectResponse
    {
        return $this->processForm($request, $service, $tenant);
    }

    private function processForm(Request $request, TenantService $service, ?Tenant $tenant): RedirectResponse
    {
        $slug = Str::slug($request->input('slug') ?: $request->input('name'));

        $centralDomain = collect(config('tenancy.central_domains', []))
            ->first(fn ($d) => !in_array($d, ['127.0.0.1', 'localhost']));

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => [
                'nullable', 'string', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenant) {
                    if (blank($value)) {
                        return;
                    }
                    $exists = \Illuminate\Support\Facades\DB::table('tenants')
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.slug')) = ?", [$value])
                        ->when($tenant?->id, fn ($q) => $q->where('id', '!=', $tenant->id))
                        ->exists();
                    if ($exists) {
                        $fail('The slug has already been taken.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($centralDomain, $tenant) {
                    if (blank($value) || !$centralDomain) {
                        return;
                    }
                    $exists = \Stancl\Tenancy\Database\Models\Domain::query()
                        ->where('domain', $value . '.' . $centralDomain)
                        ->when($tenant?->id, fn ($q) => $q->where('tenant_id', '!=', $tenant->id))
                        ->exists();
                    if ($exists) {
                        $fail(__('This subdomain is already taken. Please choose a different shop name.'));
                    }
                },
            ],
            'email'             => ['required', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'status'            => ['required', Rule::enum(TenantStatus::class)],
            'categoryIds'       => ['array'],
            'categoryIds.*'     => ['integer', Rule::exists('categories', 'id')],
            'primaryLanguageId' => ['nullable', 'integer', Rule::exists('languages', 'id')],
            'packageId'         => ['nullable', 'integer', Rule::exists('packages', 'id')],
            'trialEndsAt'       => ['nullable', 'date'],
            'domain'            => [
                'nullable', 'string', 'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                Rule::unique('domains', 'domain')->where(
                    fn ($q) => $tenant ? $q->where('tenant_id', '!=', $tenant->id) : $q
                ),
                Rule::unique('domain_requests', 'domain'),
            ],
            'profitPercentage'  => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'password'          => [$tenant ? 'nullable' : 'required', 'string', 'min:8'],
            'logoUpload'        => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['slug']  = $slug;
        $validated['phone'] = $this->sanitizePhone($validated['phone'] ?? null);
        $saved = $service->save([
            'name'                 => $validated['name'],
            'slug'                 => $validated['slug'] ?? null,
            'email'                => $validated['email'],
            'phone'                => $validated['phone'],
            'status'               => $validated['status'],
            'category_ids'         => !empty($validated['categoryIds']) ? array_map('intval', $validated['categoryIds']) : null,
            'primary_language_id'  => $validated['primaryLanguageId'] ?? null,
            'package_id'           => $validated['packageId'] ?? null,
            'trial_ends_at'        => $validated['trialEndsAt'] ?? null,
            'shop_name'            => $validated['name'],
            'domain'               => $validated['domain'] ?? null,
            'profit_percentage'    => $validated['profitPercentage'] ?? 0,
            'password'             => $validated['password'] ?? null,
        ], $tenant);

        if ($tenant && $request->hasFile('logoUpload')) {
            tenancy()->initialize($saved);
            $path    = $request->file('logoUpload')->store('appearances/logos', 'public');
            $logoUrl = tenant_asset($path);
            app(TenantPanelService::class)->saveAppearanceSettings([
                'logo_mode'    => 'image',
                'logo_path_ar' => $logoUrl,
                'logo_path_en' => $logoUrl,
            ]);
            tenancy()->end();
        }

        return redirect()
            ->route('admin.plans.users.edit', $saved)
            ->with('status', $tenant ? 'Tenant updated successfully.' : 'Tenant created successfully.');
    }

    private function viewData(): array
    {
        return [
            'statusOptions'  => TenantStatus::cases(),
            'rootCategories' => Category::query()
                ->with('translations.language')
                ->whereNull('parent_id')
                ->where('status', 'published')
                ->orderBy('id')
                ->get(),
            'languages'      => Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get(),
            'packages'       => Package::query()->with('translations.language')->get(),
            'tenant'         => null,
            'phone'          => '',
            'categoryIds'    => [],
            'billingHistory' => collect(),
            'currentLogoUrl' => null,
        ];
    }
}
