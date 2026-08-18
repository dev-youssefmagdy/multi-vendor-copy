<?php

namespace App\Livewire\Admin\Branch;

use App\Concerns\SanitizesPhoneNumber;
use App\Enums\ShippingZoneStatus;
use App\Models\Branch;
use App\Models\Country;
use App\Repositories\BranchRepository;
use App\Services\BranchService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditBranch extends Component
{
    use SanitizesPhoneNumber;

    public ?int $branchId = null;
    public string $name = '';
    public string $code = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';
    public bool $isDefault = false;
    public bool $isActive = true;
    public array $shippingZones = [];

    public function mount(?Branch $branch = null): void
    {
        if (!$branch) {
            $this->addShippingZone();
            return;
        }

        $loaded = app(BranchRepository::class)->findForEditor($branch);
        $this->branchId = $loaded->id;
        $this->name = $loaded->name;
        $this->code = $loaded->code;
        $this->phone = $loaded->phone ?? '';
        $this->email = $loaded->email ?? '';
        $this->address = $loaded->address ?? '';
        $this->city = $loaded->city ?? '';
        $this->country = $loaded->country ?? '';
        $this->isDefault = $loaded->is_default;
        $this->isActive = $loaded->is_active;

        $this->shippingZones = $loaded->shippingZones->map(fn($zone) => [
            'id' => $zone->id,
            'country_id' => $zone->country_id,
            'name' => $zone->name,
            'code' => $zone->code,
            'currency_code' => $zone->currency_code,
            'status' => $zone->status->value,
            'rates' => $zone->rates->map(fn($rate) => [
                'id' => $rate->id,
                'name' => $rate->name,
                'min_weight' => $rate->min_weight,
                'max_weight' => $rate->max_weight,
                'price' => $rate->price,
                'is_active' => $rate->is_active,
            ])->all(),
        ])->all();

        if ($this->shippingZones === []) {
            $this->addShippingZone();
        }
    }

    public function addShippingZone(): void
    {
        $this->shippingZones[] = [
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

    public function removeShippingZone(int $zoneIndex): void
    {
        unset($this->shippingZones[$zoneIndex]);
        $this->shippingZones = array_values($this->shippingZones);
    }

    public function addRate(int $zoneIndex): void
    {
        $this->shippingZones[$zoneIndex]['rates'][] = [
            'name' => '',
            'min_weight' => '',
            'max_weight' => '',
            'price' => '0.00',
            'is_active' => true,
        ];
    }

    public function removeRate(int $zoneIndex, int $rateIndex): void
    {
        unset($this->shippingZones[$zoneIndex]['rates'][$rateIndex]);
        $this->shippingZones[$zoneIndex]['rates'] = array_values($this->shippingZones[$zoneIndex]['rates']);
    }

    public function save(BranchService $service): mixed
    {
        $validated = $this->validate($this->rules());

        $branch = $service->save([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'phone' => $this->sanitizePhone($validated['phone']),
            'email' => $validated['email'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'is_default' => $validated['isDefault'],
            'is_active' => $validated['isActive'],
            'shipping_zones' => $validated['shippingZones'],
        ], $this->branchId ? Branch::query()->findOrFail($this->branchId) : null);

        session()->flash('status', $this->branchId ? 'Branch updated successfully.' : 'Branch created successfully.');

        return redirect()->route('admin.branches.edit', $branch);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:120',
                Rule::unique('branches', 'code')->ignore($this->branchId),
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
        ];
    }

    public function render()
    {
        return view('livewire.admin.branch.add-edit-branch', [
            'pageTitle' => $this->branchId ? 'Edit Branch' : 'Add Branch',
            'countries' => Country::query()->with('translations.language')->orderBy('name')->get(),
            'statusOptions' => ShippingZoneStatus::cases(),
        ]);
    }
}
