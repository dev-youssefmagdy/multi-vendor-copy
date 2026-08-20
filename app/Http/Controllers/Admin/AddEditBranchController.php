<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\SanitizesPhoneNumber;
use App\Enums\ShippingZoneStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Country;
use App\Repositories\BranchRepository;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AddEditBranchController extends Controller
{
    use SanitizesPhoneNumber;

    public function create(): View
    {
        return $this->show(null);
    }

    public function edit(Branch $branch): View
    {
        return $this->show($branch);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->processForm($request, null);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        return $this->processForm($request, $branch);
    }

    protected function show(?Branch $branch): View
    {
        $shippingZones = [];
        $phone = '';

        if ($branch) {
            $loaded = app(BranchRepository::class)->findForEditor($branch);

            $rawPhone = $loaded->phone ?? '';
            $phone = str_contains($rawPhone, 'object') ? '' : $rawPhone;

            $shippingZones = $loaded->shippingZones->map(fn ($zone) => [
                'id' => $zone->id,
                'country_id' => $zone->country_id,
                'name' => $zone->name,
                'code' => $zone->code,
                'currency_code' => $zone->currency_code,
                'status' => $zone->status->value,
                'rates' => $zone->rates->map(fn ($rate) => [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'min_weight' => $rate->min_weight,
                    'max_weight' => $rate->max_weight,
                    'price' => $rate->price,
                    'is_active' => $rate->is_active,
                ])->all(),
            ])->all();
        }

        if ($shippingZones === []) {
            $shippingZones = [static::emptyZone()];
        }

        return view('admin.branch.add-edit-branch', [
            'pageTitle' => $branch ? 'Edit Branch' : 'Add Branch',
            'branch' => $branch,
            'phone' => $phone,
            'shippingZones' => $shippingZones,
            'countries' => Country::query()->with('translations.language')->orderBy('name')->get(),
            'statusOptions' => ShippingZoneStatus::cases(),
        ]);
    }

    protected function processForm(Request $request, ?Branch $branch): RedirectResponse
    {
        $data = $request->all();
        $data['isDefault'] = $request->boolean('isDefault');
        $data['isActive'] = $request->boolean('isActive');

        foreach ($data['shippingZones'] ?? [] as $zi => $zone) {
            foreach ($zone['rates'] ?? [] as $ri => $rate) {
                $data['shippingZones'][$zi]['rates'][$ri]['is_active'] = $request->boolean(
                    "shippingZones.{$zi}.rates.{$ri}.is_active"
                );
            }
        }

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:120',
                Rule::unique('branches', 'code')->ignore($branch?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'isDefault' => ['boolean'],
            'isActive' => ['boolean'],
            'shippingZones' => ['array'],
            'shippingZones.*.id' => ['sometimes', 'integer', 'exists:shipping_zones,id'],
            'shippingZones.*.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'shippingZones.*.name' => ['required', 'string', 'max:255'],
            'shippingZones.*.code' => ['nullable', 'string', 'max:120'],
            'shippingZones.*.currency_code' => ['required', 'string', 'size:3'],
            'shippingZones.*.status' => ['required', Rule::enum(ShippingZoneStatus::class)],
            'shippingZones.*.rates' => ['array'],
            'shippingZones.*.rates.*.name' => ['required', 'string', 'max:255'],
            'shippingZones.*.rates.*.min_weight' => ['nullable', 'numeric', 'min:0'],
            'shippingZones.*.rates.*.max_weight' => ['nullable', 'numeric', 'min:0'],
            'shippingZones.*.rates.*.price' => ['required', 'numeric', 'min:0'],
            'shippingZones.*.rates.*.is_active' => ['boolean'],
        ])->validate();

        $savedBranch = app(BranchService::class)->save([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'email' => $validated['email'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'is_default' => $validated['isDefault'],
            'is_active' => $validated['isActive'],
            'shipping_zones' => $validated['shippingZones'] ?? [],
        ], $branch);

        return redirect()
            ->route('admin.branches.edit', $savedBranch)
            ->with('status', $branch ? 'Branch updated successfully.' : 'Branch created successfully.');
    }

    public static function emptyZone(): array
    {
        return [
            'country_id' => null,
            'name' => '',
            'code' => '',
            'currency_code' => 'USD',
            'status' => ShippingZoneStatus::Active->value,
            'rates' => [
                [
                    'name' => '',
                    'min_weight' => '',
                    'max_weight' => '',
                    'price' => '0.00',
                    'is_active' => true,
                ],
            ],
        ];
    }
}
