<?php

namespace App\Livewire\Tenant\Storefront;

use App\Concerns\SanitizesPhoneNumber;
use App\Enums\OrderStatus;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Country;
use Illuminate\Validation\Rule;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductRate;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfilePage extends Component
{
    use HasStorefrontLayout;
    use SanitizesPhoneNumber;

    public string $activeTab = 'orders';
    public ?string $statusFilter = null;

    // Address form
    public bool $showAddressModal = false;
    public ?int $editingAddressId = null;
    public string $addrLabel = '';
    public string $addrFullName = '';
    public string $addrPhone = '';
    public string $addrLine1 = '';
    public string $addrCity = '';
    public string $addrState = '';
    public string $addrCountry = '';
    public ?int $addrCountryId = null;
    public bool $addrIsDefault = false;

    public function mount(): void
    {
        if (!Auth::guard('storefront')->check()) {
            $this->redirect(route('tenant.storefront.login'));
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['orders', 'profile', 'wishlist', 'returns']) ? $tab : 'orders';
    }

    public function openAddressModal(?int $id = null): void
    {
        $this->resetAddressForm();
        if ($id) {
            /** @var \App\Models\Tenant\Customer $customer */
            $customer = Auth::guard('storefront')->user();
            $addr = CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->first();
            if (!$addr) return;
            $this->editingAddressId = $addr->id;
            $this->addrLabel = $addr->label ?? '';
            $this->addrFullName = $addr->full_name ?? '';
            $this->addrPhone = $addr->phone ?? '';
            $this->addrLine1 = $addr->address_line_1 ?? '';
            $this->addrCity = $addr->city ?? '';
            $this->addrState = $addr->state ?? '';
            $this->addrCountry = $addr->country ?? '';
            $this->addrCountryId = $addr->country_id ?? null;
            $this->addrIsDefault = (bool) $addr->is_default;
        }
        $this->showAddressModal = true;
    }

    public function closeAddressModal(): void
    {
        $this->showAddressModal = false;
        $this->resetAddressForm();
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addrFullName'  => 'required|string|max:100',
            'addrLine1'     => 'required|string|max:255',
            'addrCity'      => 'required|string|max:100',
            'addrCountryId' => ['required', 'integer', Rule::exists('mysql.countries', 'id')],
            'addrPhone'     => 'nullable|string|max:30',
            'addrState'     => 'nullable|string|max:100',
            'addrLabel'     => 'nullable|string|max:50',
        ]);

        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $data = [
            'customer_id'    => $customer->id,
            'label'          => $this->addrLabel ?: null,
            'full_name'      => $this->addrFullName,
            'phone'          => $this->sanitizePhone($this->addrPhone),
            'address_line_1' => $this->addrLine1,
            'city'           => $this->addrCity,
            'state'          => $this->addrState ?: null,
            'country_id'     => $this->addrCountryId,
            'country'        => Country::on('mysql')->find($this->addrCountryId)?->name ?? '',
            'is_default'     => $this->addrIsDefault,
        ];

        if ($this->addrIsDefault) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        if ($this->editingAddressId) {
            CustomerAddress::where('id', $this->editingAddressId)
                ->where('customer_id', $customer->id)
                ->update($data);
        } else {
            if (!CustomerAddress::where('customer_id', $customer->id)->exists()) {
                $data['is_default'] = true;
            }
            CustomerAddress::create($data);
        }

        $this->closeAddressModal();
        $this->dispatch('profile-swal', message: __('Address saved successfully.'), type: 'success');
    }

    public function deleteAddress(int $id): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();
        $addr = CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->first();
        if (!$addr) return;

        $wasDefault = $addr->is_default;
        $addr->delete();

        if ($wasDefault) {
            CustomerAddress::where('customer_id', $customer->id)->orderBy('id')->first()?->update(['is_default' => true]);
        }

        $this->dispatch('profile-swal', message: __('Address deleted.'), type: 'success');
    }

    public function setDefaultAddress(int $id): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();
        if (!CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->exists()) return;

        CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        CustomerAddress::where('id', $id)->update(['is_default' => true]);

        $this->dispatch('profile-swal', message: __('Default address updated.'), type: 'success');
    }

    private function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->addrLabel = '';
        $this->addrFullName = '';
        $this->addrPhone = '';
        $this->addrLine1 = '';
        $this->addrCity = '';
        $this->addrState = '';
        $this->addrCountry = '';
        $this->addrCountryId = null;
        $this->addrIsDefault = false;
    }

    public function filterStatus(?string $status): void
    {
        $this->statusFilter = $status;
    }

    public function logout(): void
    {
        Auth::guard('storefront')->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('tenant.home'));
    }

    public function reorder(string $uuid): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->first();

        if (!$order) {
            $this->toast(__('Order not found.'), 'error');
            return;
        }

        $cart = session('storefront_cart', []);

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $key = $item->product_variant_id
                ? 'v_' . $item->product_variant_id
                : 'p_' . $item->product_id;

            if (isset($cart[$key])) {
                $cart[$key]['qty'] += max(1, (int) $item->qty);
            } else {
                $cart[$key] = [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id ?: null,
                    'qty' => max(1, (int) $item->qty),
                ];
            }
        }

        session(['storefront_cart' => $cart]);
        $this->dispatch('cartUpdated');
        $this->dispatch('profile-swal', message: __('Items added to your cart.'), type: 'success');
    }

    public function cancelOrder(string $uuid): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])
            ->first();

        if (!$order) {
            $this->dispatch('profile-swal', message: __('Order not found or cannot be cancelled.'), type: 'error');
            return;
        }

        $order->update(['status' => OrderStatus::Cancelled]);
        $this->dispatch('profile-swal', message: __('Order cancelled successfully.'), type: 'success');
    }

    public function submitReview(int $productId, int $stars, string $comment): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $stars = max(1, min(5, $stars));

        if (!Product::find($productId)) {
            $this->dispatch('profile-swal', message: __('Product not found.'), type: 'error');
            return;
        }

        if (ProductRate::where('product_id', $productId)->where('customer_id', $customer->id)->exists()) {
            $this->dispatch('profile-swal', message: __('You have already reviewed this product.'), type: 'error');
            return;
        }

        ProductRate::create([
            'product_id' => $productId,
            'customer_id' => $customer->id,
            'stars' => $stars,
            'comment' => trim($comment) ?: null,
        ]);

        $this->dispatch('profile-swal', message: __('Review submitted successfully.'), type: 'success');
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $customer = Auth::guard('storefront')->user();

        $orders = $repo->customerOrders($customer);

        if ($this->statusFilter) {
            $statusEnum = OrderStatus::from($this->statusFilter);
            $orders = $orders->filter(fn($o) => $o->status === $statusEnum)->values();
        }

        $reviewedProductIds = $customer
            ? ProductRate::where('customer_id', $customer->id)->pluck('product_id')->toArray()
            : [];

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $returnRequests = $customer
            ? \App\Models\ReturnRequest::where('tenant_id', tenant()->id)
                ->where('customer_id', $customer->id)
                ->latest()
                ->get()
            : collect();

        $data = array_merge($this->sharedData(), [
            'customer' => $customer,
            'orders' => $orders,
            'returnRequests' => $returnRequests,
            'activeTab' => $this->activeTab,
            'statusFilter' => $this->statusFilter,
            'reviewedProductIds' => $reviewedProductIds,
            'addresses' => $addresses,
            'countries' => Country::with('translations.language')->orderBy('name')->get(['id', 'name', 'flag_emoji']),
            'showAddressModal' => $this->showAddressModal,
            'editingAddressId' => $this->editingAddressId,
            'addrLabel' => $this->addrLabel,
            'addrFullName' => $this->addrFullName,
            'addrPhone' => $this->addrPhone,
            'addrLine1' => $this->addrLine1,
            'addrCity' => $this->addrCity,
            'addrState' => $this->addrState,
            'addrCountry' => $this->addrCountry,
            'addrCountryId' => $this->addrCountryId,
            'addrIsDefault' => $this->addrIsDefault,
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('profile'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('My Account') . " — {$storeName}" : __('My Account'),
                'metaDescription' => '',
            ]);
    }
}
