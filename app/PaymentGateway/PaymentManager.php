<?php

namespace App\PaymentGateway;

use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use App\Models\PaymentGateway;
use App\Models\Tenant\PaymentGateway as TenantPaymentGateway;
use App\PaymentGateway\Contracts\PaymentGatewayInterface;
use App\PaymentGateway\Exceptions\PaymentException;

/**
 * PaymentManager — Strategy Context / Factory
 *
 * Resolves and caches gateway instances by their registered key.
 *
 * Config resolution order:
 *   1. Tenant DB  — payment_gateways (is_active = true) when inside a tenant context.
 *   2. Central DB — payment_gateways (status = 'active') as a fallback.
 *   3. File-based config  — config/payment-gateways.php (last resort / local dev).
 *
 * Usage:
 *   $result = app(PaymentManager::class)->gateway('stripe')->charge($charge);
 *   // or via the Facade:
 *   $result = Payment::gateway('stripe')->charge($charge);
 */
class PaymentManager
{
    /** @var PaymentGatewayInterface[] */
    private array $resolved = [];

    /**
     * Resolve and return a gateway instance by its registered key.
     */
    public function gateway(string $key): PaymentGatewayInterface
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $drivers = config('payment-gateways.drivers', []);

        if (empty($drivers[$key])) {
            throw PaymentException::gatewayNotFound($key);
        }

        $class = $drivers[$key];
        $config = $this->resolveGatewayConfig($key);

        if (!class_exists($class)) {
            throw new \RuntimeException("Payment gateway class [{$class}] not found.");
        }

        // Resolve via the container so implementations can type-hint deps
        // and receive the resolved `$config` by name in their constructor.
        $gateway = app()->make($class, ['config' => $config]);

        if (!$gateway instanceof PaymentGatewayInterface) {
            throw new \RuntimeException(
                "Gateway class [{$class}] must implement PaymentGatewayInterface."
            );
        }

        return $this->resolved[$key] = $gateway;
    }

    /**
     * Determine which gateway to use from the request (e.g. ?gateway=stripe).
     * Falls back to the configured default gateway.
     */
    public function fromRequest(?\Illuminate\Http\Request $request = null): PaymentGatewayInterface
    {
        $request ??= request();
        $key = $request->input('gateway') ?? config('payment-gateways.default', 'stripe');
        return $this->gateway($key);
    }

    /**
     * Returns the codes of all gateways that are active and usable.
     *
     * When inside a tenant context, only gateways enabled by the tenant AND
     * still active in the central `payment_gateways` table are returned.
     * Falls back to all registered driver keys when no DB records are found.
     *
     * @return string[]
     */
    public function available(): array
    {
        $drivers = array_keys(config('payment-gateways.drivers', []));

        if ($this->inTenantContext()) {
            return $this->storefrontGateways()
                ->pluck('code')
                ->filter(fn(string $code) => in_array($code, $drivers, true))
                ->values()
                ->all();
        }

        return $this->centrallyActiveGatewayCodes();
    }

    /**
     * Returns the gateway codes that are currently active in the central DB.
     * Used to cross-reference tenant gateways so that centrally-disabled
     * gateways are never surfaced on the storefront.
     *
     * Only considers `orders` type gateways.
     *
     * @return string[]
     */
    public function centrallyActiveGatewayCodes(): array
    {
        return PaymentGateway::query()
            ->where('status', 'active')
            ->where('type', PaymentGatewayType::Orders->value)
            ->pluck('code')
            ->all();
    }

    public function centrallyActiveGateways()
    {
        return PaymentGateway::query()
            ->where('status', 'active')
            ->where('type', PaymentGatewayType::Orders->value)
            ->get();
    }

    public function storefrontGateways()
    {

        if (!$this->inTenantContext()) {
            return PaymentGateway::query()
                ->where('status', 'active')
                ->where('type', PaymentGatewayType::Orders->value)
                ->with('logoFile')
                ->get()
                ->map(fn(PaymentGateway $gateway) => [
                    'source' => 'central',
                    'id' => $gateway->id,
                    'code' => $gateway->code,
                    'name' => $gateway->name,
                    'mode' => $gateway->mode?->value ?? PaymentGatewayMode::Test->value,
                    'creds' => (array) ($gateway->credentials ?? []),
                    'logo_url' => $gateway->logoFile?->full_path,
                    'payment_methods' => $this->meta($gateway->code)['payment_methods'] ?? [],
                ])
                ->values();
        }

        return TenantPaymentGateway::query()
            ->with('centralPaymentGateway.logoFile')
            ->where('is_active', true)
            ->where('hide', false)
            ->where('type', PaymentGatewayType::Orders->value)
            ->whereIn('code', $this->centrallyActiveGatewayCodes())
            ->orderBy('name')
            ->get()
            // A vendor-supplied key that failed its connection check must not be
            // offered to customers — surfacing it only leads to a failed charge.
            ->reject(fn(TenantPaymentGateway $gateway) => $gateway->use_own && $gateway->connection_status === 'not_connected')
            ->map(function (TenantPaymentGateway $gateway): array {
                $mode = $gateway->effective_mode;
                $useOwn = $gateway->use_own == 1;

                return [
                    'source' => $gateway->gateway_source,
                    'id' => $useOwn ? $gateway->id : ($gateway->centralPaymentGateway->id ?? null),
                    'code' => $gateway->code,
                    'name' => $gateway->name,
                    'mode' => $mode instanceof PaymentGatewayMode ? $mode->value : (string) $mode,
                    'creds' => $gateway->effective_credentials,
                    'use_own' => $useOwn,
                    'logo_url' => $gateway->centralPaymentGateway?->logoFile?->full_path,
                    'payment_methods' => $this->meta($gateway->code)['payment_methods'] ?? [],
                ];
            })
            ->values();
    }

    /**
     * Returns active central gateways of the `vendor_payments` type.
     *
     * Used to populate payment options in the vendor admin panel for:
     *   - order settlements (vendor-to-central)
     *   - subscription / package payments
     *   - manufacturing payment requests
     *
     * @return array<int, array{id:int,code:string,name:string,mode:string,creds:array,fee_pct:float,fee_fixed:float}>
     */
    public function vendorPaymentGateways(): \Illuminate\Support\Collection
    {
        $rows = tenancy()->central(function () {
            return PaymentGateway::query()
                ->where('status', 'active')
                ->where('type', PaymentGatewayType::VendorPayments->value)
                ->orderBy('name')
                ->get()
                ->map(fn(PaymentGateway $gw) => [
                    'id' => $gw->id,
                    'code' => $gw->code,
                    'name' => $gw->name,
                    'mode' => $gw->mode?->value ?? PaymentGatewayMode::Live->value,
                    'creds' => (array) ($gw->credentials ?? []),
                    'fee_pct' => (float) ($gw->transaction_fee_percentage ?? 0),
                    'fee_fixed' => (float) ($gw->transaction_fee_fixed ?? 0),
                ])
                ->values()
                ->all();
        });

        return collect($rows);
    }

    /**
     * Return the resolved config array (credentials + sandbox flag) for a gateway key.
     * Used by external services such as GatewayConnectionChecker.
     */
    public function getConfig(string $key): array
    {
        return $this->resolveGatewayConfig($key);
    }

    /**
     * The tenant's primary storefront gateway (is_primary = true), falling
     * back to the first available gateway when none is explicitly marked.
     * Returns null when the tenant has no usable gateway at all.
     */
    public function primaryGateway(): ?PaymentGatewayInterface
    {
        $gateways = $this->storefrontGateways();

        if ($gateways->isEmpty()) {
            return null;
        }

        $primaryCode = $this->inTenantContext()
            ? TenantPaymentGateway::query()
                ->where('is_active', true)
                ->where('is_primary', true)
                ->whereIn('code', $gateways->pluck('code')->all())
                ->value('code')
            : null;

        $code = $primaryCode ?? $gateways->first()['code'];

        return $this->gateway($code);
    }

    /**
     * Remaining active gateways (excluding the primary), in display order —
     * used to fall back when the primary gateway's charge attempt fails.
     *
     * @return PaymentGatewayInterface[]
     */
    public function fallbackGateways(): array
    {
        $gateways = $this->storefrontGateways();
        $primary = $this->primaryGateway();

        return $gateways
            ->pluck('code')
            ->reject(fn(string $code) => $primary && $code === $primary->getKey())
            ->map(fn(string $code) => $this->gateway($code))
            ->values()
            ->all();
    }

    /**
     * Capability metadata for a driver key (currencies / merchant countries /
     * customer countries / payment methods) — readable WITHOUT credentials,
     * for marketplace listings and recommendation scoring. Returns empty
     * arrays for unregistered/legacy gateways that haven't defined meta().
     *
     * @return array{currencies: string[], merchant_countries: string[], customer_countries: string[], payment_methods: string[]}
     */
    public function meta(string $key): array
    {
        $empty = ['currencies' => [], 'merchant_countries' => [], 'customer_countries' => [], 'payment_methods' => []];
        $class = config('payment-gateways.drivers')[$key] ?? null;

        if (!$class || !class_exists($class)) {
            return $empty;
        }

        try {
            $method = new \ReflectionMethod($class, 'meta');
            $method->setAccessible(true);

            return array_merge($empty, $method->invoke(null));
        } catch (\ReflectionException) {
            return $empty;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the config array for a gateway key using the DB-first resolution order.
     * Credentials must exist in the database (tenant or central); env file is not used.
     */
    private function resolveGatewayConfig(string $key): array
    {
        if ($this->inTenantContext()) {
            $tenantGateway = $this->findTenantGateway($key);
            if ($tenantGateway) {
                return array_merge($tenantGateway->effective_credentials, [
                    'sandbox' => $this->isTestMode($tenantGateway->effective_mode),
                ]);
            }
        }

        $centralGateway = $this->findCentralGateway($key);

        if ($centralGateway) {
            return array_merge((array) ($centralGateway->credentials ?? []), [
                'sandbox' => $this->isTestMode($centralGateway->mode ?? PaymentGatewayMode::Live),
            ]);
        }

        throw PaymentException::gatewayNotFound($key);
    }

    private function findTenantGateway(string $key): ?TenantPaymentGateway
    {
        if (!$this->inTenantContext()) {
            return null;
        }

        return TenantPaymentGateway::query()
            ->with('centralPaymentGateway')
            ->where('code', $key)
            ->where('is_active', true)
            ->where('hide', false)
            ->whereIn('code', $this->centrallyActiveGatewayCodes())
            ->first();
    }

    /**
     * Return a gateway instance configured with CENTRAL credentials only,
     * bypassing any tenant gateway overrides. Used for vendor-to-central
     * settlement payments where we must charge via the platform's gateway.
     *
     * Always resolves against the `vendor_payments` type gateways.
     */
    public function centralGateway(string $code): PaymentGatewayInterface
    {
        $centralModel = $this->inTenantContext()
            ? tenancy()->central(fn() => $this->findCentralGateway($code, PaymentGatewayType::VendorPayments))
            : $this->findCentralGateway($code, PaymentGatewayType::VendorPayments);

        if (!$centralModel) {
            throw PaymentException::gatewayNotFound($code);
        }

        $drivers = config('payment-gateways.drivers', []);

        if (empty($drivers[$code])) {
            throw PaymentException::gatewayNotFound($code);
        }

        $class = $drivers[$code];

        $creds = (array) ($centralModel->credentials ?? []);
        $config = array_merge($creds, [
            'sandbox' => $this->isTestMode($centralModel->mode ?? PaymentGatewayMode::Live),
        ]);

        if (!class_exists($class)) {
            throw new \RuntimeException("Payment gateway class [{$class}] not found.");
        }

        return app()->make($class, ['config' => $config]);
    }

    protected function findCentralGateway(string $key, PaymentGatewayType $type = PaymentGatewayType::Orders): ?PaymentGateway
    {
        return PaymentGateway::query()
            ->where('code', $key)
            ->where('status', 'active')
            ->where('type', $type->value)
            ->first();
    }

    private function isTestMode(PaymentGatewayMode|string|null $mode): bool
    {
        $modeValue = $mode instanceof PaymentGatewayMode
            ? $mode->value
            : (string) ($mode ?? PaymentGatewayMode::Test->value);

        return $modeValue === PaymentGatewayMode::Test->value;
    }

    /**
     * Returns true when Stancl Tenancy has been initialized for the current request.
     */
    private function inTenantContext(): bool
    {
        return !empty(tenant('id') ?? null);
    }
}
