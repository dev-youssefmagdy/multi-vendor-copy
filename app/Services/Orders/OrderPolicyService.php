<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\Tenant\SettingType;
use App\Models\Tenant;
use App\Models\Tenant\Setting;

/**
 * Tenant "order policy" settings (cancellation / refund / exchange rules), stored in the
 * tenant `settings` table under group `order_policy`, one row per key, named
 * `order_policy_{key}`. Missing or malformed values fall back to DEFAULTS.
 *
 * Values are cached per tenant for the lifetime of this instance (bound as `scoped`, so
 * that's one request / job). Pass a Tenant to read another tenant's policy from any
 * context — it is read through $tenant->run(), which restores the previous context.
 */
class OrderPolicyService
{
    public const GROUP = 'order_policy';

    public const CANCELLATION_ALLOW_PROCESSING = 'cancellation_allow_processing';

    public const CANCELLATION_WINDOW_HOURS = 'cancellation_window_hours';

    public const AUTO_REFUND_ON_CANCEL = 'auto_refund_on_cancel';

    public const EXCHANGE_ENABLED = 'exchange_enabled';

    public const RESTOCK_RETURNED_ITEMS = 'restock_returned_items';

    /** @var array<string, bool|int> */
    public const DEFAULTS = [
        self::CANCELLATION_ALLOW_PROCESSING => true,
        self::CANCELLATION_WINDOW_HOURS => 0,
        self::AUTO_REFUND_ON_CANCEL => true,
        self::EXCHANGE_ENABLED => true,
        self::RESTOCK_RETURNED_ITEMS => true,
    ];

    /** @var array<string, array<string, bool|int>> tenant id => resolved policy */
    private array $cache = [];

    /**
     * The full resolved policy for the given tenant (or the current tenant).
     *
     * @return array{cancellation_allow_processing: bool, cancellation_window_hours: int, auto_refund_on_cancel: bool, exchange_enabled: bool, restock_returned_items: bool}
     */
    public function all(?Tenant $tenant = null): array
    {
        $tenant ??= $this->currentTenant();

        if (! $tenant) {
            return self::DEFAULTS;
        }

        return $this->cache[(string) $tenant->getTenantKey()] ??= $this->runFor($tenant, fn () => $this->load());
    }

    public function get(string $key, ?Tenant $tenant = null): bool|int
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Unknown order policy key [{$key}].");
        }

        return $this->all($tenant)[$key];
    }

    /** Customers may cancel orders that are already Processing. */
    public function cancellationAllowProcessing(?Tenant $tenant = null): bool
    {
        return (bool) $this->get(self::CANCELLATION_ALLOW_PROCESSING, $tenant);
    }

    /** Hours after placement during which a customer may cancel a Processing order (0 = no limit). */
    public function cancellationWindowHours(?Tenant $tenant = null): int
    {
        return (int) $this->get(self::CANCELLATION_WINDOW_HOURS, $tenant);
    }

    /** Execute the cancellation refund right away when the gateway supports it. */
    public function autoRefundOnCancel(?Tenant $tenant = null): bool
    {
        return (bool) $this->get(self::AUTO_REFUND_ON_CANCEL, $tenant);
    }

    public function exchangeEnabled(?Tenant $tenant = null): bool
    {
        return (bool) $this->get(self::EXCHANGE_ENABLED, $tenant);
    }

    /** Default value of the inspection "restock" checkbox. */
    public function restockReturnedItems(?Tenant $tenant = null): bool
    {
        return (bool) $this->get(self::RESTOCK_RETURNED_ITEMS, $tenant);
    }

    /**
     * Persist (a subset of) the policy. Unknown keys are ignored.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, bool|int> the resolved policy after saving
     */
    public function update(array $values, ?Tenant $tenant = null): array
    {
        $tenant ??= $this->currentTenant();

        if (! $tenant) {
            throw new \RuntimeException('Order policy can only be saved for a tenant.');
        }

        $this->runFor($tenant, function () use ($values): void {
            foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
                $isBool = is_bool(self::DEFAULTS[$key]);

                Setting::query()->updateOrCreate(
                    ['name' => self::settingName($key), 'group' => self::GROUP],
                    [
                        'value' => $this->serialize($this->normalize($key, $value)),
                        'type' => $isBool ? SettingType::Boolean : SettingType::Number,
                    ],
                );
            }
        });

        unset($this->cache[(string) $tenant->getTenantKey()]);

        return $this->all($tenant);
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    public static function settingName(string $key): string
    {
        return self::GROUP.'_'.$key;
    }

    /** @return array<string, bool|int> */
    private function load(): array
    {
        $names = array_map(fn (string $key) => self::settingName($key), array_keys(self::DEFAULTS));

        $rows = Setting::query()
            ->where('group', self::GROUP)
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $policy = [];

        foreach (self::DEFAULTS as $key => $default) {
            $raw = $rows->get(self::settingName($key))?->value;
            $policy[$key] = ($raw === null || $raw === '') ? $default : $this->normalize($key, $raw);
        }

        return $policy;
    }

    private function normalize(string $key, mixed $value): bool|int
    {
        $default = self::DEFAULTS[$key];

        if (is_bool($default)) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $bool ?? $default;
        }

        return is_numeric($value) ? max(0, (int) $value) : $default;
    }

    private function serialize(bool|int $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    private function currentTenant(): ?Tenant
    {
        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }

    private function runFor(Tenant $tenant, \Closure $callback): mixed
    {
        $current = tenant();

        if ($current && $current->getTenantKey() === $tenant->getTenantKey()) {
            return $callback();
        }

        return $tenant->run($callback);
    }
}
