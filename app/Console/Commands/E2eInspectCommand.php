<?php

namespace App\Console\Commands;

use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Support\OrderPricing;
use App\Support\OrderProfitCalculator;
use Illuminate\Console\Command;

/**
 * Dedup plan Phase 8 (browser E2E, dev / CI only): read-only JSON probes the Playwright specs assert against.
 * Every expected money value is computed by the app's own code (OrderComposer::line → OrderPricing,
 * OrderProfitCalculator), never by a formula written in a spec.
 *
 *   php artisan e2e:inspect order  e2e-sync --uuid=<order uuid>   stored order + app-recomputed expectation
 *   php artisan e2e:inspect order  e2e-sync --latest              newest order of the tenant
 *   php artisan e2e:inspect stock  e2e-sync --slug=e2e-stock      central / tenant stock of a catalog product
 */
class E2eInspectCommand extends Command
{
    protected $signature = 'e2e:inspect {what : order | stock} {tenant} {--uuid=} {--latest} {--slug=}';

    protected $description = 'Phase 8: JSON probes for the browser E2E suite (order money via OrderPricing / OrderProfitCalculator, stock)';

    public function handle(): int
    {
        $tenant = Tenant::query()->find((string) $this->argument('tenant'));
        if (! $tenant || ! str_starts_with((string) $tenant->id, 'e2e-')) {
            $this->error('Only the E2E tenants (e2e-*) can be inspected.');

            return self::FAILURE;
        }

        $out = match ($this->argument('what')) {
            'order' => $tenant->run(fn () => $this->order()),
            'stock' => $this->stock($tenant),
            default => ['error' => 'unknown probe'],
        };
        tenancy()->end();

        $this->line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));

        return isset($out['error']) ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function order(): array
    {
        $query = Order::query()->with(['items.product', 'items.variant']);
        $order = $this->option('uuid')
            ? $query->where('uuid', (string) $this->option('uuid'))->first()
            : ($this->option('latest') ? $query->latest('id')->first() : null);

        if (! $order) {
            return ['error' => 'order not found'];
        }

        $countryId = (int) (($order->shipping_address['country_id'] ?? null) ?: 0) ?: null;
        $stored = [];
        $expected = [];
        foreach ($order->items as $item) {
            $stored[] = [
                'id' => $item->id,
                'product_type' => $item->product_type,
                'catalog_product_id' => $item->catalog_product_id,
                'variant_type' => $item->variant_type,
                'catalog_variant_id' => $item->catalog_variant_id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'qty' => (int) $item->qty,
                'cost' => (float) $item->cost,
                'profit_amount' => (float) $item->profit_amount,
                'price' => (float) $item->price,
                'sub_total' => (float) $item->sub_total,
                'fixed_shipping' => (float) $item->fixed_shipping,
                'discount' => (float) $item->discount,
                'discount_source' => $item->discount_source,
                'tax' => (float) $item->tax,
                'grand_total' => (float) $item->grand_total,
            ];
            $product = $item->product;
            $expected[] = $product
                ? array_diff_key(OrderComposer::line($product, $item->variant, (int) $item->qty, $countryId), ['is_own' => 1])
                : null;
        }

        $expectedLines = array_values(array_filter($expected));
        $itemsGrand = OrderPricing::order($expectedLines, 0.0)['items_grand_total'];
        $coupon = ($code = $order->appliedCouponCode()) ? app(CouponCatalog::class)->findByCode($code) : null;
        $couponDiscount = $coupon ? OrderPricing::couponDiscount((string) ($coupon->type->value ?? $coupon->type), (float) $coupon->value, $itemsGrand) : 0.0;
        $expectedOrder = OrderPricing::order($expectedLines, (float) $order->shipping_charge, $couponDiscount, $coupon ? CouponCatalog::orderSource($coupon) : null);

        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'status' => $order->status?->value ?? (string) $order->status,
            'paid' => (bool) $order->paid,
            'payment_method' => $order->payment_method,
            'collected_by' => $order->collected_by,
            'stock_deducted_at' => $order->stock_deducted_at?->toIso8601String(),
            'stock_restored_at' => $order->stock_restored_at?->toIso8601String(),
            'refunded_amount' => (float) ($order->refunded_amount ?? 0),
            'coupon_code' => $order->appliedCouponCode(),
            'coupon_type' => $order->coupon_type,
            'catalog_coupon_id' => $order->catalog_coupon_id,
            'country_id' => $countryId,
            'stored' => [
                'items' => $stored,
                'items_grand_total' => (float) $order->items_grand_total,
                'shipping_charge' => (float) $order->shipping_charge,
                'coupon_discount' => (float) $order->coupon_discount,
                'coupon_source' => $order->coupon_source,
                'grand_total' => (float) $order->grand_total,
                'platform_profit' => (float) $order->platform_profit,
                'vendor_profit' => (float) $order->vendor_profit,
            ],
            'expected' => ['items' => $expected, 'order' => $expectedOrder],
            'calculator' => [
                'is_void' => OrderProfitCalculator::isFinanciallyVoid($order),
                'net_order_total' => OrderProfitCalculator::netOrderTotal($order),
                'platform_profit' => OrderProfitCalculator::platformProfitForOrder($order),
                'vendor_profit' => OrderProfitCalculator::vendorProfitForOrder($order),
                // the invariant evaluated by the app's own rounding helper (OrderPricing::money)
                'split_sum' => OrderPricing::money((float) $order->platform_profit + (float) $order->vendor_profit),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function stock(Tenant $tenant): array
    {
        $slug = (string) $this->option('slug');
        $central = CentralProduct::query()->where('slug', $slug)->first(['id', 'stock', 'sold_count', 'manage_stock']);
        if (! $central) {
            return ['error' => 'product not found'];
        }

        $tenantSide = $tenant->run(function () use ($central) {
            $merged = ProductCatalog::enabled();
            $copy = \App\Models\Tenant\Product::withoutGlobalScopes()->where('central_product_id', $central->id)->first();
            $override = \Illuminate\Support\Facades\Schema::connection('tenant')->hasTable('tenant_product_overrides')
                ? \Illuminate\Support\Facades\DB::connection('tenant')->table('tenant_product_overrides')->where('central_product_id', $central->id)->first(['stock_override'])
                : null;
            $product = $merged ? ProductCatalog::query()->where('central_product_id', $central->id)->first() : $copy;

            return [
                'mode' => $merged ? 'central' : 'sync',
                'copy_stock' => $copy?->stock === null ? null : (int) $copy->stock,
                'stock_override' => $override?->stock_override === null ? null : (int) $override->stock_override,
                // first variant (every E2E catalog product has one): what the storefront cart check sees
                'available' => $product ? app(\App\Services\Tenant\StockService::class)->availableStock($product, $product->variants()->first()) : null,
            ];
        });

        return [
            'central_product_id' => $central->id,
            'central_stock' => (int) $central->stock,
            'central_sold_count' => (int) $central->sold_count,
            'central_variant_stock' => CentralVariant::query()->where('product_id', $central->id)->pluck('stock', 'id')->map(fn ($v) => (int) $v)->all(),
            'tenant' => $tenantSide,
        ];
    }
}
