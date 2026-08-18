<?php

namespace App\Livewire\Tenant\Customer;

use App\Concerns\SanitizesPhoneNumber;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\City;
use App\Models\Country;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Livewire\Attributes\Computed;

class CustomerDetailPage extends TenantPage
{
    use InteractsWithTenantUi;
    use SanitizesPhoneNumber;

    public int $customerId = 0;
    public string $activeTab = 'profile';

    // ── Profile edit fields ────────────────────────────────────────────────
    public string $fullName = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $countryId = '';
    public string $cityId = '';
    public bool $active = true;
    public string $password = '';
    public string $passwordConfirmation = '';

    // ── Address modal state ────────────────────────────────────────────────
    public bool $addressModalOpen = false;
    public ?int $editAddressId = null;
    public string $addrLabel = '';
    public string $addrFullName = '';
    public string $addrEmail = '';
    public string $addrPhone = '';
    public string $addrLine1 = '';
    public string $addrCity = '';
    public string $addrState = '';
    public string $addrCountry = '';
    public bool $addrIsDefault = false;

    // ── Delete confirm state ───────────────────────────────────────────────
    public ?int $deleteAddressId = null;

    protected function pageMeta(): array
    {
        return [
            'title' => $this->fullName ?: 'Customer Detail',
            'badge' => 'CRM',
            'description' => 'Full customer profile — orders, payment history, saved addresses and inline editing.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.customer.customer-detail-page';
    }

    public function mount(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        $this->customerId = $customerId;
        $this->syncProfileFields($customer);
    }

    protected function pageData(): array
    {
        $detail = app(TenantPanelRepository::class)->customerDetail(
            Customer::query()->findOrFail($this->customerId)
        );

        return array_merge(parent::pageData(), [
            'detail'    => $detail,
            'activeTab' => $this->activeTab,
            'countries' => Country::with('translations.language')->orderBy('name')->get(['id', 'name'])->pluck('name', 'id'),
            'cities'    => $this->countryId
                ? City::where('country_id', $this->countryId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    // ── Tab navigation ─────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ── Profile update ─────────────────────────────────────────────────────

    public function saveProfile(TenantPanelService $service): void
    {
        $this->validate([
            'fullName'            => 'required|string|max:191',
            'email'               => 'required|email|max:191|unique:customers,email,' . $this->customerId,
            'phone'               => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'countryId'           => 'nullable|integer',
            'cityId'              => 'nullable|integer',
            'password'            => 'nullable|string|min:8|same:passwordConfirmation',
            'passwordConfirmation'=> 'nullable|string',
        ]);

        $payload = [
            'full_name'  => $this->fullName,
            'email'      => $this->email,
            'phone'      => $this->sanitizePhone($this->phone),
            'address'    => $this->address,
            'country_id' => filled($this->countryId) ? (int) $this->countryId : null,
            'city_id'    => filled($this->cityId) ? (int) $this->cityId : null,
            'active'     => $this->active,
        ];

        if (filled($this->password)) {
            $payload['password'] = bcrypt($this->password);
            $this->password = '';
            $this->passwordConfirmation = '';
        }

        Customer::query()->where('id', $this->customerId)->update($payload);
        $this->syncProfileFields(Customer::query()->find($this->customerId));
        $this->toast('Profile updated successfully.');
    }

    public function toggleActive(): void
    {
        $customer = Customer::query()->findOrFail($this->customerId);
        $customer->update(['active' => !$customer->active]);
        $this->active = (bool) $customer->active;
        $this->toast($this->active ? 'Customer activated.' : 'Customer deactivated.');
    }

    public function updatedCountryId(): void
    {
        $this->cityId = '';
    }

    // ── Address CRUD ───────────────────────────────────────────────────────

    public function openAddressModal(?int $addressId = null): void
    {
        $this->resetAddressFields();
        $this->editAddressId = $addressId;

        if ($addressId) {
            $addr = CustomerAddress::query()->where('customer_id', $this->customerId)->findOrFail($addressId);
            $this->addrLabel = $addr->label ?? '';
            $this->addrFullName = $addr->full_name ?? '';
            $this->addrEmail = $addr->email ?? '';
            $this->addrPhone = $addr->phone ?? '';
            $this->addrLine1 = $addr->address_line_1 ?? '';
            $this->addrCity = $addr->city ?? '';
            $this->addrState = $addr->state ?? '';
            $this->addrCountry = $addr->country ?? '';
            $this->addrIsDefault = (bool) $addr->is_default;
        }

        $this->addressModalOpen = true;
    }

    public function closeAddressModal(): void
    {
        $this->addressModalOpen = false;
        $this->resetAddressFields();
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addrFullName' => 'nullable|string|max:191',
            'addrEmail' => 'nullable|email|max:191',
            'addrPhone' => 'nullable|string|max:50',
            'addrLine1' => 'required|string|max:500',
            'addrCity' => 'nullable|string|max:191',
            'addrState' => 'nullable|string|max:191',
            'addrCountry' => 'nullable|string|max:191',
        ]);

        $data = [
            'customer_id' => $this->customerId,
            'label' => $this->addrLabel ?: null,
            'full_name' => $this->addrFullName ?: null,
            'email' => $this->addrEmail ?: null,
            'phone' => $this->sanitizePhone($this->addrPhone),
            'address_line_1' => $this->addrLine1,
            'city' => $this->addrCity ?: null,
            'state' => $this->addrState ?: null,
            'country' => $this->addrCountry ?: null,
            'is_default' => $this->addrIsDefault,
        ];

        if ($this->addrIsDefault) {
            CustomerAddress::query()->where('customer_id', $this->customerId)->update(['is_default' => false]);
        }

        if ($this->editAddressId) {
            CustomerAddress::query()
                ->where('customer_id', $this->customerId)
                ->where('id', $this->editAddressId)
                ->update($data);
            $this->toast('Address updated.');
        } else {
            CustomerAddress::query()->create($data);
            $this->toast('Address added.');
        }

        $this->closeAddressModal();
    }

    public function confirmDeleteAddress(int $addressId): void
    {
        $this->deleteAddressId = $addressId;
    }

    public function deleteAddress(): void
    {
        if ($this->deleteAddressId) {
            CustomerAddress::query()
                ->where('customer_id', $this->customerId)
                ->where('id', $this->deleteAddressId)
                ->delete();
            $this->toast('Address deleted.');
        }
        $this->deleteAddressId = null;
    }

    public function cancelDeleteAddress(): void
    {
        $this->deleteAddressId = null;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function syncProfileFields(Customer $customer): void
    {
        $this->fullName             = $customer->full_name ?? '';
        $this->email                = $customer->email ?? '';
        $this->phone                = $customer->phone ?? '';
        $this->address              = $customer->address ?? '';
        $this->countryId            = (string) ($customer->country_id ?? '');
        $this->cityId               = (string) ($customer->city_id ?? '');
        $this->active               = (bool) $customer->active;
        $this->password             = '';
        $this->passwordConfirmation = '';
    }

    protected function resetAddressFields(): void
    {
        $this->editAddressId = null;
        $this->addrLabel = '';
        $this->addrFullName = '';
        $this->addrEmail = '';
        $this->addrPhone = '';
        $this->addrLine1 = '';
        $this->addrCity = '';
        $this->addrState = '';
        $this->addrCountry = '';
        $this->addrIsDefault = false;
    }
}
