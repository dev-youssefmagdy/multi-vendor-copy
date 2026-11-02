<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when(($filters['show_deleted'] ?? false), fn($query) => $query->onlyTrashed())
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery
                                ->where('field', 'name')
                                ->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['category_id'] ?? null), function ($query) use ($filters) {
                $query->whereHas('categories', fn(Builder $categoryQuery) => $categoryQuery->whereKey($filters['category_id']));
            })
            ->when(filled($filters['delivery_scope'] ?? null), fn($query) => $query->where('delivery_scope', $filters['delivery_scope']))
            ->when(in_array($filters['stock'] ?? '', ['in', 'partial', 'out'], true), function ($query) use ($filters) {
                $this->applyStockFilter($query, $filters['stock']);
            })
            ->when(!empty($filters['image_search_ids'] ?? null), function ($query) use ($filters) {
                $query->whereIn('id', $filters['image_search_ids']);
            })
            ->tap(fn ($query) => $this->applyListSort($query, $filters['sort'] ?? null, $filters['dir'] ?? 'desc'))
            ->paginate($perPage);
    }

    /**
     * PANELS-UPDATES-TABLE-SORTING: header sort for the central products list. Falls back to the curated
     * order_number sequence; the key is whitelisted here (the Livewire page validates it as well).
     */
    protected function applyListSort(Builder $query, ?string $key, string $direction): void
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $title = fn () => \Illuminate\Support\Facades\DB::table('translations')
            ->select('value')
            ->whereColumn('translations.translatable_id', 'products.id')
            ->where('translations.translatable_type', (new Product())->getMorphClass())
            ->where('translations.field', 'name')
            ->limit(1);

        match ($key) {
            'title' => $query->orderBy($title(), $direction),
            'sku' => $query->orderBy('sku', $direction),
            'factory' => $query->orderBy('factory', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'price' => $query->orderByRaw('COALESCE(sale_price, base_price) '.$direction),
            'weight' => $query->orderBy('weight_grams', $direction),
            'stock' => $query->orderBy('stock', $direction),
            'status' => $query->orderBy('status', $direction),
            default => $query->orderBy('order_number'),
        };

        if ($key !== null && $key !== '') {
            $query->orderBy('order_number');
        }
    }

    /**
     * @param 'in'|'partial'|'out' $stock
     */
    protected function applyStockFilter(Builder $query, string $stock): void
    {
        $query->where('manage_stock', true)->where(function (Builder $q) use ($stock) {
            match ($stock) {
                'out' => $q->where(function (Builder $noVariants) {
                    $noVariants->whereDoesntHave('variants')->where('stock', '<=', 0);
                })->orWhere(function (Builder $hasVariants) {
                    $hasVariants->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '>', 0));
                }),
                'in' => $q->where(function (Builder $noVariants) {
                    $noVariants->whereDoesntHave('variants')->where('stock', '>', 0);
                })->orWhere(function (Builder $hasVariants) {
                    $hasVariants->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '<=', 0));
                }),
                'partial' => $q
                    ->whereHas('variants', fn($v) => $v->where('stock', '<=', 0))
                    ->whereHas('variants', fn($v) => $v->where('stock', '>', 0)),
                default => null,
            };
        });
    }

    public function stats(): array
    {
        ['amount' => $salesAmount, 'orders' => $salesOrders] = $this->aggregateTenantSales();

        return [
            'products_total' => Product::query()->count(),
            'categories_with_items' => Category::query()->has('products')->count(),
            'categories_total' => Category::query()->count(),
            'sales_amount' => $salesAmount,
            'sales_orders' => $salesOrders,
            'affiliate_amount' => 0,
            'affiliate_orders' => 0,
        ];
    }

    private function aggregateTenantSales(): array
    {
        $completedStatuses = [
            OrderStatus::Completed->value,
            OrderStatus::Shipped->value,
            OrderStatus::Delivered->value,
        ];

        $totalAmount = 0.0;
        $totalOrders = 0;

        foreach (Tenant::all() as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $totalAmount += (float) DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    // ->whereIn('orders.status', $completedStatuses)
                    ->sum('order_items.sub_total');

                $totalOrders += DB::table('orders')
                    // ->whereIn('status', $completedStatuses)
                    ->count();
            } catch (\Throwable) {
                // skip tenants with missing/inaccessible databases
            }
        }

        tenancy()->end();

        return ['amount' => $totalAmount, 'orders' => $totalOrders];
    }

    public function findForEditor(Product $product): Product
    {
        return $this->baseQuery()->findOrFail($product->getKey());
    }

    public function searchForPicker(string $search = '', int $page = 1, int $perPage = 15): array
    {
        $query = Product::query()
            ->select('id', 'slug')
            ->with(['translations' => fn($q) => $q->where('field', 'name')])
            ->when(filled($search), function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($q2) use ($like) {
                    $q2->where('slug', 'like', $like)
                        ->orWhereHas('translations', fn($t) => $t->where('field', 'name')->where('value', 'like', $like));
                });
            })
            ->orderBy('slug');

        $total = (clone $query)->count();

        $items = $query->forPage($page, $perPage)->get()
            ->mapWithKeys(fn(Product $product) => [
                (int) $product->id => $product->translations->first()?->value
                    ?? $product->slug
                    ?? 'Product #' . $product->id,
            ])
            ->all();

        return [
            'items' => $items,
            'has_more' => ($page * $perPage) < $total,
        ];
    }

    public function productNamesForIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->with(['translations' => fn($q) => $q->where('field', 'name')])
            ->get()
            ->mapWithKeys(fn(Product $product) => [
                (int) $product->id => $product->translations->first()?->value
                    ?? $product->slug
                    ?? 'Product #' . $product->id,
            ])
            ->all();
    }

    protected function baseQuery(): Builder
    {
        return Product::query()->with([
            'translations.language',
            'categories.translations.language',
            'categories.parent.translations.language',
            'categories.parent.parent.translations.language',
            'shippingZones',
            'countries',
            'variations.translations.language',
            'variants.options.translations.language',
            'variants.files',
            'files',
            'badges',
            'labels',
        ]);
    }
}
