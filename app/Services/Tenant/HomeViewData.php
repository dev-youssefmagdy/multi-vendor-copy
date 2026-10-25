<?php

namespace App\Services\Tenant;

use App\Models\Country;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Setting;
use App\Support\CacheVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Small data helpers for storefront theme Blade views, replacing queries that
 * used to run inline in the views. Results are memoized per request (stored
 * on the request attributes, so every `app(HomeViewData::class)` instance in
 * the same request shares them) and, where safe, cached across requests behind
 * the same versioned keys StorefrontRepository::cacheRemember() uses.
 */
class HomeViewData
{
    private const MEMO_ATTRIBUTE = 'homeViewData.memo';

    /**
     * Raw `settings.value` per name (null when the row is missing), exactly as
     * `Setting::query()->where('name', $n)->value('value')` returned it.
     * Values are not translated here: value() selects only the `value`
     * column, so the model has no key and its translated accessor falls back
     * to the raw column — hence no locale in the cache key.
     *
     * @param  string[]  $names
     * @return array<string, string|null>
     */
    public function settings(array $names): array
    {
        $names = array_values(array_unique($names));
        $sorted = $names;
        sort($sorted);

        $all = $this->memo('settings:' . implode(',', $sorted), fn () => $this->remember(
            'view_settings:' . md5(implode(',', $sorted)),
            ['Setting'],
            function () use ($sorted): array {
                $values = array_fill_keys($sorted, null);
                $found = [];
                // Keep the first matching row per name, like value() (first()) did.
                foreach (Setting::query()->toBase()->whereIn('name', $sorted)->get(['name', 'value']) as $row) {
                    if (! isset($found[$row->name])) {
                        $found[$row->name] = true;
                        $values[$row->name] = $row->value;
                    }
                }

                return $values;
            },
        ));

        $result = [];
        foreach ($names as $name) {
            $result[$name] = $all[$name] ?? null;
        }

        return $result;
    }

    /**
     * Country for an id, loaded at most once per request (same model as
     * `Country::find($id)`; translated `name` resolves at access time).
     */
    public function country($id): ?Country
    {
        if (! $id) {
            return null;
        }

        return $this->memo('country:' . $id, function () use ($id): ?Country {
            return Country::find($id);
        });
    }

    /** The visitor's selected country (`session('storefront_country_id')`). */
    public function currentCountry(): ?Country
    {
        return $this->country(session('storefront_country_id'));
    }

    /**
     * 30-day sold quantity per product id (product_id => total_qty), as the
     * elora v6 trending_now section computed inline. Cached for a short TTL:
     * besides order writes (tag bumps) the 30-day window moves with time, so
     * the figures may lag by at most $ttl seconds.
     *
     * @param  int[]  $productIds
     */
    public function soldQtyLast30Days(array $productIds, int $ttl = 600): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        $ids = array_values(array_unique(array_map('intval', $productIds)));
        sort($ids);
        $key = 'sold_qty_30d:' . md5(implode(',', $ids));

        return collect($this->memo($key, fn () => $this->remember($key, ['Order', 'OrderItem', 'Product'], fn (): array => OrderItem::query()
            ->selectRaw('COALESCE(order_items.product_id, product_variants.product_id) as product_id, SUM(order_items.qty) as total_qty')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where(function ($q) use ($productIds): void {
                $q->whereIn('order_items.product_id', $productIds)
                    ->orWhereIn('product_variants.product_id', $productIds);
            })
            ->groupBy(DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'))
            ->pluck('total_qty', 'product_id')
            ->all(), $ttl)));
    }

    /** Per-request memo shared by all instances (request-scoped, tenant-scoped). */
    private function memo(string $key, \Closure $callback): mixed
    {
        $request = request();
        $key = (tenant()?->id ?? 'central') . ':' . $key;
        $memo = $request->attributes->get(self::MEMO_ATTRIBUTE, []);

        if (! array_key_exists($key, $memo)) {
            $memo[$key] = $callback();
            $request->attributes->set(self::MEMO_ATTRIBUTE, $memo);
        }

        return $memo[$key];
    }

    /** Same versioned file-cache scheme as StorefrontRepository::cacheRemember(). */
    private function remember(string $key, array $tags, \Closure $callback, ?int $ttl = null): mixed
    {
        $ttl ??= (int) config('cache.storefront.default_ttl', 3600);
        $version = collect($tags)->map(fn ($tag) => CacheVersion::get($tag))->implode('.');
        $fullKey = 'storefront:' . (tenant()?->id ?? 'central') . ":{$key}:v{$version}";

        return Cache::driver('file')->remember($fullKey, $ttl, $callback);
    }
}
