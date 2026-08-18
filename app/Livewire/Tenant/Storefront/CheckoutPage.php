<?php

namespace App\Livewire\Tenant\Storefront;

use App\Concerns\SanitizesPhoneNumber;
use App\Enums\DeliveryScope;
use App\Enums\OrderStatus;
use App\Enums\ShippingZoneStatus;
use App\Enums\Tenant\CouponType;
use App\Livewire\Tenant\Storefront\Concerns\CalculatesFreeShipping;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\PaymentGateway;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantProductVariant;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\OrderLifecycleService;
use App\Services\Tenant\ShippingEstimateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class CheckoutPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;
    use SanitizesPhoneNumber;
    use CalculatesFreeShipping;

    // ─── All mutable form state under a single structured property ───────────
    public array $data = [
        'shipping' => [
            'address_id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'country_id' => null,
        ],
        'billing' => [
            'same_as_shipping' => true,
            'address_id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
        ],
        'payment' => [
            'method' => null,    // null | 'cod' | gateway code
            'gateway_id' => null,
            'stripe_token' => '',
            'authnet_desc' => '',
            'authnet_value' => '',
            'twoco_token' => '',
        ],
        'coupon' => [
            'code' => '',
        ],
        'modal' => [
            'show' => false,
            'context' => 'shipping',  // 'shipping' | 'billing'
            'address_id' => null,
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'line1' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'country_id' => null,
            'is_default' => false,
        ],
    ];

    // ─── Mount ───────────────────────────────────────────────────────────────

    public function mount(): void
    {
        if (!Auth::guard('storefront')->check()) {
            session()->put('url.intended', request()->url());
            $this->redirect(route('tenant.storefront.login', ['redirect' => route('tenant.storefront.checkout')]));
            return;
        }

        $repo = app(StorefrontRepository::class);
        if ($repo->cartItems() === []) {
            $this->redirect(route('tenant.storefront.cart'));
            return;
        }

        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $defaultAddr = $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->first();

        if ($defaultAddr) {
            $this->data['shipping']['address_id'] = $defaultAddr->id;
            $this->data['billing']['address_id'] = $defaultAddr->id;
            $this->fillAddressData('shipping', $defaultAddr);
        } else {
            $this->data['shipping']['name'] = $customer->full_name ?? '';
            $this->data['shipping']['email'] = $customer->email ?? '';
            $this->data['shipping']['phone'] = $customer->phone ?? '';
            $this->data['shipping']['address'] = $customer->address ?? '';
        }

        $this->data['coupon']['code'] = session('storefront_coupon', '');

        $cartItems = $repo->cartItems();
        $this->dispatch('tracking-event', name: 'initiate_checkout', params: [
            'content_ids' => collect($cartItems)->pluck('product_id')->values()->all(),
            'num_items' => collect($cartItems)->sum('qty'),
            'value' => collect($cartItems)->sum(fn($item) => (float) ($item['subtotal'] ?? 0)),
        ]);
    }

    // ─── Address selection ────────────────────────────────────────────────────

    public function selectShippingAddress(int $id): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();
        $addr = $customer->addresses()->findOrFail($id);

        $this->data['shipping']['address_id'] = $addr->id;
        $this->data['shipping']['country'] = $addr->country;
        $this->fillAddressData('shipping', $addr);
    }

    public function selectBillingAddress(int $id): void
    {
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();
        $addr = $customer->addresses()->findOrFail($id);

        $this->data['billing']['address_id'] = $addr->id;
        $this->fillAddressData('billing', $addr);
    }

    // ─── Address modal ────────────────────────────────────────────────────────

    public function openAddressModal(string $context = 'shipping', $new = false, ?int $addressId = null): void
    {
        $this->data['modal']['context'] = $context;
        $this->resetAddressModal();

        if (!$new) {
            $resolvedId = $addressId ?? ($this->data[$context]['address_id'] ?? null);
            if ($resolvedId) {
                /** @var \App\Models\Tenant\Customer $customer */
                $customer = Auth::guard('storefront')->user();
                $addr = $customer->addresses()->find($resolvedId);

                if ($addr) {
                    $this->fillAddressModal($addr);
                }
            }
        }

        $this->data['modal']['show'] = true;
    }

    public function closeAddressModal(): void
    {
        $this->data['modal']['show'] = false;
        $this->resetAddressModal();
    }

    public function saveNewAddress(): void
    {
        $this->validate([
            'data.modal.full_name' => 'required|string|max:120',
            'data.modal.email' => 'nullable|email|max:120',
            'data.modal.phone' => 'nullable|string|max:30',
            'data.modal.line1' => 'required|string|max:300',
            'data.modal.city' => 'nullable|string|max:100',
            'data.modal.state' => 'nullable|string|max:100',
            'data.modal.country' => 'nullable|string|max:100',
            'data.modal.country_id' => 'nullable|integer',
        ], [], [
            'data.modal.full_name' => 'full name',
            'data.modal.line1' => 'street address',
        ]);

        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        if ($this->data['modal']['is_default']) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $payload = [
            'customer_id' => $customer->id,
            'full_name' => $this->data['modal']['full_name'],
            'email' => $this->data['modal']['email'] ?: null,
            'phone' => $this->sanitizePhone($this->data['modal']['phone']),
            'address_line_1' => $this->data['modal']['line1'],
            'city' => $this->data['modal']['city'] ?: null,
            'state' => $this->data['modal']['state'] ?: null,
            'country' => $this->data['modal']['country'] ?: null,
            'country_id' => $this->data['modal']['country_id'] ?: null,
            'is_default' => $this->data['modal']['is_default'],
        ];

        $editingAddressId = $this->data['modal']['address_id'] ?? null;
        if ($editingAddressId) {
            $addr = $customer->addresses()->findOrFail($editingAddressId);
            $addr->update($payload);
            $addr->refresh();
        } else {
            $addr = CustomerAddress::create($payload);
        }

        $scope = $this->data['modal']['context'];
        $this->data[$scope]['address_id'] = $addr->id;
        $this->fillAddressData($scope, $addr);

        $this->data['modal']['show'] = false;
        $this->resetAddressModal();
    }

    // ─── Cart mutations (qty / remove on checkout page) ──────────────────────

    public function removeFromCart(string $key): void
    {
        $cart = session('storefront_cart', []);
        if (array_key_exists($key, $cart)) {
            unset($cart[$key]);
            session(['storefront_cart' => $cart]);
            $this->toast(__('Item removed from your cart.'), 'success');
        }
        $this->dispatch('cartUpdated');

        // Redirect to cart if no items left
        $repo = app(StorefrontRepository::class);
        if ($repo->cartItems() === []) {
            $this->redirect(route('tenant.storefront.cart'));
        }
    }

    public function updateQty(string $key, int $qty): void
    {
        if ($qty < 1) {
            $this->removeFromCart($key);
            return;
        }

        $cart = session('storefront_cart', []);
        if (!array_key_exists($key, $cart)) {
            return;
        }

        // Stock check: qty is the new total quantity
        [$product, $variant] = $this->resolveCartEntryModels($key, $cart[$key]);
        if ($product) {
            $stockError = $this->checkProductStock($product, $variant, $qty, 0);
            if ($stockError !== null) {
                $this->dispatch('storefront-toast', message: $stockError, type: 'error');
                return;
            }
        }

        $cart[$key]['qty'] = $qty;
        session(['storefront_cart' => $cart]);
        $this->dispatch('cartUpdated');
    }

    // ─── Payment ──────────────────────────────────────────────────────────────

    public function selectPayment(string $method, ?int $gatewayId = null): void
    {
        $this->data['payment']['method'] = $method;
        $this->data['payment']['gateway_id'] = $gatewayId;
        $this->dispatch('paymentMethodChanged');
    }

    // ─── Coupon ───────────────────────────────────────────────────────────────

    public function applyCoupon(): void
    {
        $code = trim($this->data['coupon']['code']);

        if ($code === '') {
            $this->addError('data.coupon.code', __('Please enter a coupon code.'));
            $this->toast(__('Please enter a coupon code.'), 'error');
            return;
        }

        $coupon = Coupon::query()
            ->where('code', $code)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->first();

        if (!$coupon) {
            $this->addError('data.coupon.code', __('This coupon code is invalid or has expired.'));
            $this->toast(__('This coupon code is invalid or has expired.'), 'error');
            return;
        }

        $cartTotal = app(StorefrontRepository::class)->cartTotal();

        if ($coupon->minimum_spend !== null && $cartTotal < (float) $coupon->minimum_spend) {
            $minimumSpend = number_format((float) $coupon->minimum_spend, 2);
            $message = __('This coupon requires a minimum spend of :amount.', ['amount' => $minimumSpend]);
            $this->addError('data.coupon.code', $message);
            $this->toast($message, 'warning');
            return;
        }

        session(['storefront_coupon' => $coupon->code]);
        $this->data['coupon']['code'] = $coupon->code;
        $this->resetErrorBag('data.coupon.code');
        $this->toast(__('Coupon applied successfully.'), 'success');
    }

    public function removeCoupon(): void
    {
        session()->forget('storefront_coupon');
        $this->data['coupon']['code'] = '';
        $this->resetErrorBag('data.coupon.code');
        $this->toast(__('Coupon removed successfully.'), 'success');
    }

    // ─── Place Order ──────────────────────────────────────────────────────────

    public function placeOrder(): void
    {
        $billingRequired = !$this->data['billing']['same_as_shipping'];

        $this->validate([
            'data.payment.method' => 'required|string',
            'data.shipping.name' => 'required|string|max:120',
            'data.shipping.email' => 'required|email|max:120',
            'data.shipping.phone' => 'required|string|max:30',
            'data.shipping.address' => 'required|string|max:300',
            'data.billing.name' => $billingRequired ? 'required|string|max:120' : 'nullable',
            'data.billing.email' => $billingRequired ? 'required|email|max:120' : 'nullable',
            'data.billing.phone' => $billingRequired ? 'required|string|max:30' : 'nullable',
            'data.billing.address' => $billingRequired ? 'required|string|max:300' : 'nullable',
        ], [], [
            'data.payment.method' => 'payment method',
            'data.shipping.name' => 'shipping name',
            'data.shipping.email' => 'shipping email',
            'data.shipping.phone' => 'shipping phone',
            'data.shipping.address' => 'shipping address',
            'data.billing.name' => 'billing name',
            'data.billing.email' => 'billing email',
            'data.billing.phone' => 'billing phone',
            'data.billing.address' => 'billing address',
        ]);

        $repo = app(StorefrontRepository::class);
        $cartItems = $repo->cartItems();

        if ($cartItems === []) {
            $this->toast(__('Your cart is empty.'), 'error');
            return;
        }

        // ── Stock validation (warn only — OOS items no longer block submission) ──
        $stockWarnings = [];
        foreach ($cartItems as $item) {
            $product = $item['product'] ?? null;
            $variant = $item['variant'] ?? null;
            if (!$product) {
                continue;
            }
            $stockError = $this->checkProductStock($product, $variant, (int) $item['qty'], 0);
            if ($stockError !== null) {
                $stockWarnings[] = $stockError;
            }
        }
        foreach ($stockWarnings as $warning) {
            $this->dispatch('storefront-toast', message: $warning, type: 'warning');
        }
        // ─────────────────────────────────────────────────────────────────────

        $shippingCalculation = $this->resolveShippingCalculation($cartItems);

        if (!$shippingCalculation['available']) {
            $this->toast($shippingCalculation['message'] ?: __('Shipping is not available for the selected address.'), 'error');
            return;
        }

        // ── Partition cart items into own-product vs central-product groups ──────
        $ownItems = [];
        $centralItems = [];
        $ownProductCost = 0.0;
        $centralProductCost = 0.0;

        foreach ($cartItems as $item) {
            $orderItem = $this->buildOrderItemPayload(
                $item,
                (float) ($shippingCalculation['line_fees'][$item['key']] ?? 0),
                (float) ($shippingCalculation['line_weights'][$item['key']] ?? 0),
            );

            if (!$orderItem) {
                continue;
            }

            $product = $item['product'] ?? null;
            $variant = $item['variant'] ?? null;
            $ownerCost = $this->resolveOwnerCost($product, $variant) * $orderItem['qty'];

            if ($product instanceof TenantProduct) {
                $pricing = $product->storefrontPricing($variant, $this->resolveShippingCountryId());
                $flashSale = $pricing['flash_sale'] ?? null;

                if (($pricing['is_flash_sale'] ?? false) && $flashSale?->central_flash_sale_id !== null) {
                    $perUnitDiscount = (float) ($pricing['base_price'] ?? 0) - (float) ($pricing['current_price'] ?? 0);
                    $ownerCost = max(0.0, $ownerCost - ($perUnitDiscount * $orderItem['qty']));
                }
            }

            if ($product && ($product->is_own_product || $product->is_tenant_owned)) {
                $ownItems[] = $orderItem;
                $ownProductCost += $ownerCost;
            } else {
                $centralItems[] = $orderItem;
                $centralProductCost += $ownerCost;
            }
        }


        $allItems = array_merge($ownItems, $centralItems);

        if ($allItems === []) {
            $this->toast(__('Your cart contains invalid items.'), 'error');
            return;
        }

        $cartTotal = $repo->cartTotal();
        $appliedCoupon = null;
        $discountAmount = 0.0;
        $discountPercent = 0.0;

        if ($couponCode = session('storefront_coupon')) {
            $appliedCoupon = Coupon::query()->where('code', $couponCode)->first();
        }

        if ($appliedCoupon) {
            $discountAmount = match ($appliedCoupon->type) {
                CouponType::Percentage => $cartTotal * (float) $appliedCoupon->value / 100,
                CouponType::Fixed => min((float) $appliedCoupon->value, $cartTotal),
            };
            $discountPercent = $cartTotal > 0 ? round($discountAmount / $cartTotal * 100, 4) : 0.0;
        }

        $cartFinalTotal = max(0.0, $cartTotal - $discountAmount);
        $gatewayId = $this->data['payment']['gateway_id'];
        $gateways = app(PaymentManager::class)->storefrontGateways();

        $selectedGateway = $gateways->where('code', $this->data['payment']['method'])->firstWhere('id', $gatewayId);

        if ($gatewayId && !$selectedGateway) {
            $this->toast(__('Selected payment gateway is no longer available. Please choose another payment method.'), 'error');
            return;
        }

        $usingOwnGateway = $selectedGateway !== null && ($selectedGateway['source'] ?? '') === 'tenant';
        $usingCentralGateway = $selectedGateway !== null && !$usingOwnGateway && $gatewayId;

        $shippingAmount = (float) ($shippingCalculation['amount'] ?? 0);
        $centralCostAndShippingCost = round($centralProductCost + $shippingAmount, 2);
        $ownerProfit = !$usingOwnGateway
            ? round($centralCostAndShippingCost, 2)
            : round(max(0.0, $cartFinalTotal - $centralCostAndShippingCost), 2);

        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $vendorGatewayId = null;
        $vendorGatewayFee = null;
        $vendorOwes = !$usingCentralGateway;

        if ($vendorOwes && $usingOwnGateway && $gatewayId) {
            $tenantGateway = PaymentGateway::find($gatewayId);
            if ($tenantGateway?->central_payment_gateway_id) {
                $centralGateway = \App\Models\PaymentGateway::find($tenantGateway->central_payment_gateway_id);
                if ($centralGateway) {
                    $vendorGatewayId = $centralGateway->id;
                    $vendorTotalDue = round($centralCostAndShippingCost, 2);
                    $vendorGatewayFee = $centralGateway->calculateFee($vendorTotalDue);
                }
            }
        }

        $shippingAddress = [
            'name' => $this->data['shipping']['name'],
            'email' => $this->data['shipping']['email'],
            'phone' => $this->sanitizePhone($this->data['shipping']['phone']),
            'address' => $this->data['shipping']['address'],
            'country' => $this->resolveShippingCountry(),
            'country_id' => $this->resolveShippingCountryId(),
        ];

        $isMixedCart = $ownItems !== [] && $centralItems !== [];
        $orderGroupUuid = $isMixedCart ? (string) Str::uuid() : null;

        // ── Shipping is charged on the admin/central order only ────────────────
        $allSubtotal = array_sum(array_column($allItems, 'sub_total'));
        $ownSubtotal = array_sum(array_column($ownItems, 'sub_total'));
        $centralSubtotal = array_sum(array_column($centralItems, 'sub_total'));

        if ($isMixedCart) {
            $ownShipping = 0.0;
            $centralShipping = $shippingAmount;
        } else {
            $ownShipping = $shippingAmount;
            $centralShipping = $shippingAmount;
        }

        $lifecycle = app(OrderLifecycleService::class);

        // ── Helper to build and persist one order ─────────────────────────────
        $createOrder = function (array $orderItems, float $shipping, float $ownerProfitForOrder, float $vendorCostForOrder, ) use ($customer, $selectedGateway, $gatewayId, $appliedCoupon, $discountPercent, $shippingCalculation, $vendorOwes, $vendorGatewayId, $vendorGatewayFee, $shippingAddress, $orderGroupUuid, ): Order {
            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'order_group_uuid' => $orderGroupUuid,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'paid' => false,
                'payment_method' => $selectedGateway['code'] ?? 'cod',
                'payment_gateway_id' => $gatewayId ?: null,
                'discount_id' => $appliedCoupon?->id,
                'discount_percentage' => $discountPercent,
                'tax_percentage' => 0,
                'shipping_zone_rate_id' => $shippingCalculation['zone_rate_id'],
                'shipping_charge' => $shipping,
                'owner_profit' => $ownerProfitForOrder,
                'vendor_cost' => $vendorOwes ? round($vendorCostForOrder, 2) : 0,
                'vendor_gateway_id' => $vendorOwes ? $vendorGatewayId : null,
                'vendor_gateway_fee' => $vendorOwes ? $vendorGatewayFee : 0,
                'shipping_address' => $shippingAddress,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            return $order;
        };

        if ($isMixedCart) {
            // Central-products order: owner_profit = centralCost + centralShipping
            $centralOwnerProfit = !$usingOwnGateway
                ? round($centralProductCost + $centralShipping, 2)
                : round(max(0.0, $centralSubtotal - ($centralProductCost + $centralShipping)), 2);

            // Own-products order: owner_profit = ownShipping only (no central cost)
            $ownOwnerProfit = !$usingOwnGateway
                ? round($ownShipping, 2)
                : round(max(0.0, $ownSubtotal - $ownShipping), 2);

            $centralOrder = $createOrder(
                $centralItems,
                $centralShipping,
                $centralOwnerProfit,
                $centralProductCost + $centralShipping,
            );

            $ownOrder = $createOrder(
                $ownItems,
                $ownShipping,
                $ownOwnerProfit,
                $ownShipping,
            );

            $lifecycle->recordPlaced($centralOrder->fresh(['items', 'customer', 'paymentGateway']));
            $lifecycle->recordPlaced($ownOrder->fresh(['items', 'customer', 'paymentGateway']));

            if ($appliedCoupon) {
                $lifecycle->recordCouponApplied($centralOrder->fresh(), $appliedCoupon->code, round($discountAmount * ($centralSubtotal / $allSubtotal), 2));
                $lifecycle->recordCouponApplied($ownOrder->fresh(), $appliedCoupon->code, round($discountAmount * ($ownSubtotal / $allSubtotal), 2));
            }

            // Payment redirects to the central-products order; companion (own) is paid together
            $primaryOrder = $centralOrder;
            $companionOrder = $ownOrder;
        } else {
            $order = $createOrder($allItems, $shippingAmount, $ownerProfit, $centralCostAndShippingCost);

            $lifecycle->recordPlaced($order->fresh(['items', 'customer', 'paymentGateway']));

            if ($appliedCoupon) {
                $lifecycle->recordCouponApplied($order->fresh(), $appliedCoupon->code, round($discountAmount, 2));
            }

            $primaryOrder = $order;
            $companionOrder = null;
        }

        if ($gatewayId) {
            if ($companionOrder) {
                // Stash companion UUID so PaymentController can mark it paid alongside the primary
                session(['storefront_companion_order_uuid' => $companionOrder->uuid]);
            }
            $this->stashInlineTokens($selectedGateway['code']);
            $this->redirect(route('tenant.payment.charge', [
                $selectedGateway['code'],
                $primaryOrder->uuid,
            ]));
            return;
        }

        $lifecycle->recordCodConfirmed($primaryOrder->fresh());
        if ($companionOrder) {
            $lifecycle->recordCodConfirmed($companionOrder->fresh());
        }
        session()->forget(['storefront_cart', 'storefront_coupon']);
        $this->dispatch('cartUpdated');
        $this->redirect(route('tenant.storefront.order-status', $primaryOrder->uuid));
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $cartItems = $repo->cartItems();
        $cartTotal = $repo->cartTotal();
        $sharedData = $this->sharedData();

        // ── Currency helpers ──────────────────────────────────────────────────
        $currency = $sharedData['currentCurrency'] ?? null;
        $symbol = $currency?->symbol ?? '$';
        $rate = (float) ($currency?->conversion_rate ?? 1.0);
        $formatMoney = fn(float $v): string => $symbol . ' ' . number_format($v * $rate, 2);

        // ── Coupon / discount ─────────────────────────────────────────────────
        $appliedCoupon = null;
        if ($couponSession = session('storefront_coupon')) {
            $appliedCoupon = Coupon::query()->where('code', $couponSession)->first();
            if ($appliedCoupon && $appliedCoupon->minimum_spend !== null && $cartTotal < (float) $appliedCoupon->minimum_spend) {
                $minimumSpend = number_format((float) $appliedCoupon->minimum_spend, 2);
                session()->forget('storefront_coupon');
                $this->data['coupon']['code'] = '';
                $this->addError('data.coupon.code', __('Coupon removed: your cart total is below the minimum spend of :amount.', ['amount' => $minimumSpend]));
                $this->toast(__('Coupon removed: your cart total is below the minimum spend of :amount.', ['amount' => $minimumSpend]), 'error');
                $appliedCoupon = null;
            }
        }

        $cartDiscount = 0.0;
        if ($appliedCoupon) {
            $cartDiscount = match ($appliedCoupon->type) {
                CouponType::Percentage => $cartTotal * (float) $appliedCoupon->value / 100,
                CouponType::Fixed => min((float) $appliedCoupon->value, $cartTotal),
            };
        }

        $cartFinalTotal = max(0.0, $cartTotal - $cartDiscount);
        $shippingCalculation = $this->resolveShippingCalculation($cartItems);

        // Free-shipping progress bar — use the same country as the selected shipping address
        $shippingThreshold = $this->detectFreeShippingThreshold($this->resolveShippingCountryId());
        $cartWeight = $this->cartWeightGrams($cartItems);
        $shippingPct = $shippingThreshold > 0
            ? min(100, (int) round($cartWeight / $shippingThreshold * 100))
            : 0;
        $remainingForFreeShipping = max(0, $shippingThreshold - $cartWeight);

        $shippingEstimate = app(ShippingEstimateService::class)->estimate();
        $totalDue = $cartFinalTotal + (float) ($shippingCalculation['amount'] ?? 0);

        // ── Active gateways ───────────────────────────────────────────────────
        $gateways = app(PaymentManager::class)->storefrontGateways();

        // ── Inline-card gateway detection ─────────────────────────────────────
        $inlineCardCodes = ['stripe', 'authorize_net', '2checkout'];
        $hasStripe = false;
        $hasAuthorizeNet = false;
        $has2Checkout = false;
        $inlineGatewayMap = [];

        foreach ($gateways as $gw) {
            if (!in_array($gw['code'], $inlineCardCodes, true)) {
                continue;
            }

            $inlineGatewayMap[$gw['code']] = [
                'code' => $gw['code'],
                'creds' => $gw['creds'],
                'mode' => $gw['mode'] ?? 'test',
            ];

            match ($gw['code']) {
                'stripe' => ($hasStripe = true),
                'authorize_net' => ($hasAuthorizeNet = true),
                '2checkout' => ($has2Checkout = true),
                default => null,
            };
        }

        $activeInlineGateway = $inlineGatewayMap[$this->data['payment']['method']] ?? null;
        $authorizeNetGateway = $gateways->firstWhere('code', 'authorize_net');
        $authNetSandbox = ($authorizeNetGateway['mode'] ?? 'test') === 'test';

        // ── Cart items enriched for display ───────────────────────────────────
        $enrichedItems = array_map(static function (array $item) use ($formatMoney): array {
            $product = $item['product'];
            $variant = $item['variant'];

            return array_merge($item, [
                'display_name' => $product->translationValue('name') ?? $product->slug,
                'display_image' => $variant?->thumbnail_url
                    ?? $product->centralProduct?->primary_image_url
                    ?? $product->primary_image_url ?? null,
                'display_variant' => $variant?->display_label ?? null,
                'formatted_subtotal' => $formatMoney((float) $item['subtotal']),
            ]);
        }, $cartItems);

        // ── Customer addresses ────────────────────────────────────────────────
        /** @var \App\Models\Tenant\Customer $customer */
        $customer = Auth::guard('storefront')->user();
        $savedAddresses = $customer?->addresses()->get() ?? collect();

        return view($this->pageView('checkout'), array_merge($sharedData, [
            'cartItems' => $enrichedItems,
            'cartTotal' => $cartTotal,
            'cartDiscount' => $cartDiscount,
            'cartFinalTotal' => $totalDue,
            'cartShipping' => (float) ($shippingCalculation['amount'] ?? 0),
            'appliedCoupon' => $appliedCoupon,
            'gateways' => $gateways,
            'savedAddresses' => $savedAddresses,
            'hasAddresses' => $savedAddresses->isNotEmpty(),
            'formattedTotal' => $formatMoney($cartTotal),
            'formattedDiscount' => $formatMoney($cartDiscount),
            'formattedShipping' => $formatMoney((float) ($shippingCalculation['amount'] ?? 0)),
            'formattedFinalTotal' => $formatMoney($totalDue),
            'shippingAvailable' => (bool) ($shippingCalculation['available'] ?? true),
            'shippingMessage' => $shippingCalculation['message'] ?? null,
            'shippingZoneLabel' => $shippingCalculation['zone_label'] ?? null,
            'shippingRateLabel' => $shippingCalculation['rate_label'] ?? null,
            'shippingEstimate' => $shippingEstimate,
            'shippingThreshold' => $shippingThreshold,
            'cartWeight' => $cartWeight,
            'shippingPct' => $shippingPct,
            'remainingForFreeShipping' => $remainingForFreeShipping,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
            'authNetSandbox' => $authNetSandbox,
            'countries' => \App\Models\Country::query()->with('translations.language')->orderBy('name')->get(['id', 'name', 'iso2', 'flag_emoji']),
        ]))->layout($this->storefrontLayout(), [
                    'title' => ($storeName = $repo->storeName()) ? __('Checkout') . " — {$storeName}" : __('Checkout'),
                    'metaDescription' => '',
                ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function fillAddressData(string $scope, CustomerAddress $addr): void
    {
        $this->data[$scope]['name'] = $addr->full_name;
        $this->data[$scope]['email'] = $addr->email ?? '';
        $this->data[$scope]['phone'] = $addr->phone ?? '';
        $this->data[$scope]['country'] = $addr->country ?? '';
        $this->data[$scope]['address'] = collect([
            $addr->address_line_1,
            $addr->city,
            $addr->state,
            $addr->country,
        ])->filter()->implode(', ');
        if ($scope === 'shipping') {
            $this->data['shipping']['country_id'] = $addr->country_id ?: null;
        }
    }

    private function fillAddressModal(CustomerAddress $addr): void
    {
        $this->data['modal'] = array_merge($this->data['modal'], [
            'address_id' => $addr->id,
            'full_name' => $addr->full_name,
            'email' => $addr->email ?? '',
            'phone' => $addr->phone ?? '',
            'line1' => $addr->address_line_1 ?? '',
            'city' => $addr->city ?? '',
            'state' => $addr->state ?? '',
            'country' => $addr->country ?? '',
            'country_id' => $addr->country_id ?: null,
            'is_default' => (bool) $addr->is_default,
        ]);
    }

    private function resetAddressModal(): void
    {
        $this->data['modal'] = array_merge($this->data['modal'], [
            'address_id' => null,
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'line1' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'country_id' => null,
            'is_default' => false,
        ]);
        $this->resetErrorBag([
            'data.modal.full_name',
            'data.modal.line1',
            'data.modal.email',
            'data.modal.phone',
            'data.modal.city',
            'data.modal.state',
            'data.modal.country',
            'data.modal.country_id',
        ]);
    }

    private function stashInlineTokens(string $gatewayCode): void
    {
        if ($gatewayCode === 'stripe' && $this->data['payment']['stripe_token']) {
            session(['pgtoken_stripe_stripeToken' => $this->data['payment']['stripe_token']]);
        } elseif (
            $gatewayCode === 'authorize_net'
            && $this->data['payment']['authnet_desc']
            && $this->data['payment']['authnet_value']
        ) {
            session([
                'pgtoken_authorize_net_opaqueDataDescriptor' => $this->data['payment']['authnet_desc'],
                'pgtoken_authorize_net_opaqueDataValue' => $this->data['payment']['authnet_value'],
            ]);
        } elseif ($gatewayCode === '2checkout' && $this->data['payment']['twoco_token']) {
            session(['pgtoken_2checkout_2co_token' => $this->data['payment']['twoco_token']]);
        }
    }

    private function resolveShippingCalculation(array $cartItems): array
    {
        $lineWeights = collect($cartItems)
            ->mapWithKeys(fn(array $item) => [(string) ($item['key'] ?? uniqid('cart_', true)) => $this->resolveCartItemWeight($item)])
            ->all();

        $lineFees = collect($lineWeights)
            ->map(fn() => 0.0)
            ->all();

        $shippableItems = collect($cartItems)
            ->filter(function (array $item): bool {
                $centralProduct = $item['product']?->centralProduct;

                return $centralProduct instanceof CentralProduct
                    && (bool) $centralProduct->requires_shipping
                    && $centralProduct->delivery_scope !== DeliveryScope::Digital;
            })
            ->map(function (array $item) use ($lineWeights): array {
                $item['line_weight'] = (float) ($lineWeights[$item['key']] ?? 0);

                return $item;
            })
            ->values();

        if ($shippableItems->isEmpty()) {
            return [
                'available' => true,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => null,
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        // Resolve the country_id from the shipping address
        $countryId = $this->resolveShippingCountryId();

        if (!$countryId) {
            return [
                'available' => false,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => __('Please select a shipping country to calculate delivery charges.'),
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        // Find an active shipping zone for this country
        $zone = \App\Models\ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->where('country_id', $countryId)
            ->with(['rates' => fn($query) => $query->where('is_active', true)->orderBy('min_weight')->orderBy('max_weight')])
            ->first();

        if (!$zone) {
            return [
                'available' => false,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => __('Shipping is not available to your selected country.'),
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $totalWeight = round((float) $shippableItems->sum('line_weight'), 3);
        $rate = $zone->rates->first(fn($zoneRate) => $this->rateMatchesWeight($zoneRate, $totalWeight));

        if (!$rate) {
            // Zone exists for this country but no matching rate → free shipping
            return [
                'available' => true,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => $zone->name,
                'rate_label' => __('Free'),
                'message' => null,
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $amount = round((float) $rate->price, 2);

        return [
            'available' => true,
            'amount' => $amount,
            'zone_rate_id' => $rate->id,
            'zone_label' => $zone->name,
            'rate_label' => $rate->name,
            'message' => null,
            'line_fees' => $this->allocateShippingFees($amount, $shippableItems, $lineFees),
            'line_weights' => $lineWeights,
        ];
    }

    private function eligibleShippingZonesForProduct(?CentralProduct $product, $activeZones)
    {
        if (!$product instanceof CentralProduct) {
            return collect();
        }

        if ($product->delivery_scope === DeliveryScope::SelectedZones) {
            $assignedZoneIds = $product->relationLoaded('shippingZones')
                ? $product->shippingZones->pluck('id')
                : $product->shippingZones()->pluck('shipping_zones.id');

            return $activeZones->whereIn('id', $assignedZoneIds)->values();
        }

        return $activeZones->values();
    }

    private function rateMatchesWeight(object $rate, float $weight): bool
    {
        $grams = $weight * 1000;
        $minWeight = $rate->min_weight !== null ? (float) $rate->min_weight : null;
        $maxWeight = $rate->max_weight !== null ? (float) $rate->max_weight : null;

        return ($minWeight === null || $grams >= $minWeight)
            && ($maxWeight === null || $grams <= $maxWeight);
    }

    private function allocateShippingFees(float $amount, $shippableItems, array $lineFees): array
    {
        if ($amount <= 0 || $shippableItems->isEmpty()) {
            return $lineFees;
        }

        $metrics = $shippableItems
            ->mapWithKeys(fn(array $item) => [$item['key'] => max((float) ($item['line_weight'] ?? 0), 0)])
            ->all();

        $totalMetric = array_sum($metrics);

        if ($totalMetric <= 0) {
            $metrics = $shippableItems
                ->mapWithKeys(fn(array $item) => [$item['key'] => max(1, (int) ($item['qty'] ?? 1))])
                ->all();
            $totalMetric = array_sum($metrics);
        }

        $remaining = round($amount, 2);
        $lastKey = array_key_last($metrics);

        foreach ($metrics as $key => $metric) {
            if ($key === $lastKey) {
                $lineFees[$key] = round($remaining, 2);
                continue;
            }

            $allocated = round($amount * ($metric / $totalMetric), 2);
            $lineFees[$key] = $allocated;
            $remaining = round($remaining - $allocated, 2);
        }

        return $lineFees;
    }

    private function resolveShippingCountryId(): ?int
    {
        $addressId = $this->data['shipping']['address_id'] ?? null;
        $inlineCountryId = $this->data['shipping']['country_id'] ?? null;

        return app(\App\Services\Tenant\CustomerCountryResolver::class)->resolveId(
            $addressId ? (int) $addressId : null,
            $inlineCountryId ? (int) $inlineCountryId : null,
        );
    }

    private function resolveShippingCountry(): string
    {
        $addressId = $this->data['shipping']['address_id'] ?? null;

        if ($addressId) {
            /** @var \App\Models\Tenant\Customer|null $customer */
            $customer = Auth::guard('storefront')->user();
            $address = $customer?->addresses()->select(['id', 'country'])->find($addressId);

            if (filled($address?->country)) {
                return trim((string) $address->country);
            }
        }

        $segments = collect(explode(',', (string) ($this->data['shipping']['address'] ?? '')))
            ->map(fn(string $segment) => trim($segment))
            ->filter();

        return (string) ($segments->last() ?? '');
    }

    private function normalizeShippingToken(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->toString();
    }

    private function resolveCartItemWeight(array $cartItem): float
    {
        $product = $cartItem['product'] ?? null;
        $variant = $cartItem['variant'] ?? null;
        $qty = max(1, (int) ($cartItem['qty'] ?? 1));

        $variantWeight = ($variant instanceof TenantProductVariant)
            ? (int) ($variant->centralVariant?->weight_grams ?? 0)
            : 0;

        $weightGrams = $variantWeight > 0
            ? $variantWeight
            : (int) ($product?->centralProduct?->weight_grams ?? 0);

        if ($weightGrams <= 0) {
            return 0.0;
        }

        return round(($weightGrams / 1000) * $qty, 3);
    }

    private function buildOrderItemPayload(array $cartItem, float $shippingFee = 0.0, ?float $weight = null): ?array
    {
        $product = $cartItem['product'] ?? null;

        if (!$product instanceof TenantProduct) {
            return null;
        }

        $variant = $cartItem['variant'] ?? null;
        if (!$variant instanceof TenantProductVariant || (int) $variant->product_id !== (int) $product->id) {
            $variant = null;
        }

        $pricing = $product->storefrontPricing($variant, $this->resolveShippingCountryId());
        $qty = max(1, (int) ($cartItem['qty'] ?? 1));
        $price = round((float) $pricing['current_price'], 2);

        $flashDiscount = $pricing['is_flash_sale']
            ? round(((float) $pricing['base_price'] - $price) * $qty, 2)
            : 0.0;

        return [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'qty' => $qty,
            'price' => $price,
            'sub_total' => round($price * $qty, 2),
            'discount' => $flashDiscount,
            'tax' => 0,
            'weight' => round((float) ($weight ?? $this->resolveCartItemWeight($cartItem)), 3),
            'shipping_fee' => 0,
        ];
    }

    private function resolveOwnerCost(?TenantProduct $product, ?TenantProductVariant $variant): float
    {
        if (!$product) {
            return 0.0;
        }

        $fixedShippingCost = $product->fixedShippingCostForCountry($this->resolveShippingCountryId() ?? 0) ?? 0.0;


        // Own products (created by the tenant, not from the central catalog):
        // the vendor owns the product and its full revenue — central collects
        // only the shipping fee, so there is no central product cost to recover.
        if ($product->is_own_product) {
            return 0.0;
        }

        // Tenant-owned products assigned from central to this tenant specifically:
        // central's profit is shipping fees only (zone-based + fixed), not the
        // product price.  The shipping amount is already folded into
        // $shippingCalculation['amount'] so returning 0 here means
        // centralCostAndShippingCost = shippingAmount, which flows correctly into
        // owner_profit (central gateway) or vendor_cost (tenant gateway).
        if ($product->is_tenant_owned) {
            return 0.0;
        }

        if ($variant && (int) $variant->product_id === (int) $product->id) {
            return (float) (($variant->real_price ?? 0) + $fixedShippingCost);
        }

        $centralProduct = $product->centralProduct;
        $centralPrice = $centralProduct?->sale_price ?: $centralProduct?->base_price;

        return (float) (($centralPrice ?? $product->default_price ?? 0) + $fixedShippingCost);
    }
}
