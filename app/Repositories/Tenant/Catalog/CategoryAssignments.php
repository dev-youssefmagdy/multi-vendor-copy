<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\Catalog\CategoryKey;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Category writes of a tenant whose `catalog_source.categories` is `central` (dedup plan phase 4). Never writes central
 * (invariant 1):
 *
 *  - central category: `tenant_category_overrides` (active / featured / order_number / thumb file) and its pairs in
 *    `tenant_category_product_overrides` (sort, hide a central pair, vendor link to a pair central lacks);
 *  - own category: the tenant `categories` row (+ typed parent) and `category_product` rows as before.
 *
 * Every method bumps the tenant `Category` + `Product` cache tags (storefront menus / counts / listings).
 */
final class CategoryAssignments
{
    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    // ── Category fields ─────────────────────────────────────────────────────

    /**
     * Vendor-owned fields of a merged category. Central: override row (a value equal to central's current default is
     * stored as NULL = keep following central). Own: the tenant row.
     *
     * @param  array{active?: bool, featured?: bool, order_number?: int|null}  $attributes
     */
    public function update(MergedCategory $category, array $attributes): void
    {
        $attributes = array_intersect_key($attributes, array_flip(['active', 'featured', 'order_number']));
        if ($attributes === []) {
            return;
        }

        if (! $category->isCentral()) {
            Category::query()->whereKey($category->source_id)->update($attributes + ['updated_at' => now()]);
            $this->bump();

            return;
        }

        $central = $this->db()->table(CentralTable::qualified('categories'))->where('id', $category->source_id)->first(['is_featured', 'order_number']);
        $defaults = ['active' => true, 'featured' => (bool) ($central->is_featured ?? false), 'order_number' => (int) ($central->order_number ?? 0)];
        $values = [];
        foreach ($attributes as $field => $value) {
            $value = $field === 'order_number' ? ($value === null ? null : (int) $value) : (bool) $value;
            $values[$field] = $value === null || $value === $defaults[$field] ? null : $value;
        }

        $this->upsertOverride((int) $category->source_id, $values);
        $this->bump();
    }

    /**
     * Vendor texts of a CENTRAL category in the languages central lacks (tenant-only / AI languages, §4.2): tenant
     * `translations` rows `translatable_type = central_category`, keyed by the central id. Platform languages are
     * ignored (read-only).
     *
     * @param  array<string, array<string, mixed>>  $translations  locale => field => value (panel form shape)
     */
    public function saveTenantOnlyTranslations(MergedCategory $category, array $translations): void
    {
        if (! $category->isCentral()) {
            throw new InvalidArgumentException('Own categories keep their translations on the tenant row.');
        }

        $platform = array_flip(CategoryCatalog::centralLocales());
        $languages = $this->db()->table('languages')->pluck('id', 'code')->all();
        $fields = ['name', 'slug', 'description', 'meta_keywords', 'meta_description'];
        $now = now();

        foreach ($translations as $locale => $values) {
            if (isset($platform[$locale]) || ! isset($languages[$locale]) || ! is_array($values)) {
                continue;
            }

            $rows = [];
            $name = trim((string) ($values['name'] ?? ''));
            $slugInput = trim((string) ($values['slug'] ?? ''));
            $values['slug'] = $name === '' && $slugInput === ''
                ? ''
                : $this->uniqueCentralTenantSlug(\Illuminate\Support\Str::slug($slugInput !== '' ? $slugInput : $name), (int) $languages[$locale], (string) $locale, (int) $category->source_id);
            foreach ($fields as $field) {
                $value = trim((string) ($values[$field] ?? ''));
                if ($value !== '') {
                    $rows[] = [
                        'language_id' => (int) $languages[$locale], 'translatable_type' => CategoryCatalog::CENTRAL_TRANSLATION_TYPE,
                        'translatable_id' => (int) $category->source_id, 'field' => $field, 'value' => $value,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }

            $this->db()->table('translations')->where('translatable_type', CategoryCatalog::CENTRAL_TRANSLATION_TYPE)
                ->where('translatable_id', $category->source_id)->where('language_id', $languages[$locale])->delete();
            if ($rows !== []) {
                $this->db()->table('translations')->insert($rows);
            }
        }

        CacheVersion::bumpMany(['Category', 'Translation']);
    }

    /** Unique slug for the tenant-only language text of a central category (own + other platform + other tenant-only; D3). */
    private function uniqueCentralTenantSlug(string $base, int $languageId, string $locale, int $centralId): string
    {
        $base = $base ?: \Illuminate\Support\Str::random(8);
        $slug = $base;
        $index = 1;

        while (
            $this->db()->table('translations')->where('language_id', $languageId)->where('field', 'slug')->where('value', $slug)
                ->where(fn ($q) => $q->where('translatable_type', Category::class)
                    ->orWhere(fn ($q2) => $q2->where('translatable_type', CategoryCatalog::CENTRAL_TRANSLATION_TYPE)->where('translatable_id', '!=', $centralId)))
                ->exists()
            || \App\Models\Tenant\Catalog\MergedCategoryTranslation::query()
                ->where('translatable_id', '<', \App\Support\Catalog\CategoryKey::OWN_OFFSET)
                ->where('translatable_id', '!=', $centralId)
                ->where('language_id', $languageId)->where('field', 'slug')->where('value', $slug)->exists()
        ) {
            $index++;
            $slug = sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }

    /** Sort order of the merged list (CategorySortController): keys / refs in display order. */
    public function reorder(array $values): void
    {
        $positions = [];
        foreach (array_values($values) as $index => $value) {
            $key = CategoryKey::keyOf($value);
            if ($key !== null && ! isset($positions[$key])) {
                $positions[$key] = $index;
            }
        }
        if ($positions === []) {
            return;
        }

        $categories = MergedCategory::query()->whereKey(array_keys($positions))->get();
        $centralIds = $categories->filter(fn (MergedCategory $c) => $c->isCentral())->map(fn (MergedCategory $c) => (int) $c->source_id)->all();
        $defaults = $centralIds === [] ? [] : $this->db()->table(CentralTable::qualified('categories'))->whereIn('id', $centralIds)->pluck('order_number', 'id')->all();
        $overrides = $centralIds === [] ? [] : $this->db()->table(CategoryCatalog::OVERRIDES)->whereIn('central_category_id', $centralIds)->pluck('order_number', 'central_category_id')->all();
        $now = now();
        $touched = [];

        foreach ($categories as $category) {
            $position = $positions[(int) $category->getKey()];
            if (! $category->isCentral()) {
                if ((int) $category->order_number !== $position) {
                    Category::query()->whereKey($category->source_id)->update(['order_number' => $position, 'updated_at' => $now]);
                }

                continue;
            }

            $id = (int) $category->source_id;
            $target = $position === (int) ($defaults[$id] ?? 0) ? null : $position;
            $current = array_key_exists($id, $overrides) ? ($overrides[$id] === null ? null : (int) $overrides[$id]) : null;
            if ($target === $current) {
                continue;
            }
            array_key_exists($id, $overrides)
                ? $this->db()->table(CategoryCatalog::OVERRIDES)->where('central_category_id', $id)->update(['order_number' => $target, 'updated_at' => $now])
                : $this->db()->table(CategoryCatalog::OVERRIDES)->insert(['central_category_id' => $id, 'order_number' => $target, 'created_at' => $now, 'updated_at' => $now]);
            $touched[] = $id;
        }

        if ($touched !== []) {
            $this->db()->table(CategoryCatalog::OVERRIDES)->whereIn('central_category_id', $touched)
                ->whereNull('active')->whereNull('featured')->whereNull('order_number')->whereNull('thumb_file_id')->delete();
        }
        $this->bump();
    }

    /** Vendor thumb of a CENTRAL category (tenant `files` id; null = back to the central thumb). */
    public function setThumb(MergedCategory $category, ?int $fileId): void
    {
        if (! $category->isCentral()) {
            throw new InvalidArgumentException('Own category thumbs are tenant files of the category row.');
        }

        $this->upsertOverride((int) $category->source_id, ['thumb_file_id' => $fileId]);
        $this->bump();
    }

    /**
     * Create / update an OWN category (fields as TenantPanelService::saveCategory()). `parent` = key or ref of any
     * merged category (central or own) or null = root.
     */
    public function saveOwn(Category $own, array $fields, mixed $parent): Category
    {
        $ref = $parent === null || $parent === '' ? null : CategoryKey::parse($parent);
        if ($parent !== null && $parent !== '' && $ref === null) {
            throw new InvalidArgumentException('Invalid parent category.');
        }

        $own->fill([
            'central_category_id' => null,
            'parent_id' => $ref?->isTenant() ? $ref->id : null,
            'order_number' => (int) ($fields['order_number'] ?? 0),
            'active' => (bool) ($fields['active'] ?? true),
            'featured' => (bool) ($fields['featured'] ?? false),
        ]);
        $own->setAttribute('parent_type', $ref?->source);
        $own->setAttribute('catalog_parent_id', $ref?->id);
        $own->save();
        $this->bump();

        return $own;
    }

    private function upsertOverride(int $centralId, array $values): void
    {
        $now = now();
        $exists = $this->db()->table(CategoryCatalog::OVERRIDES)->where('central_category_id', $centralId)->exists();
        if ($exists) {
            $this->db()->table(CategoryCatalog::OVERRIDES)->where('central_category_id', $centralId)->update($values + ['updated_at' => $now]);
        } else {
            $this->db()->table(CategoryCatalog::OVERRIDES)->insert($values + ['central_category_id' => $centralId, 'created_at' => $now, 'updated_at' => $now]);
        }

        // An all-NULL row means "follow central" for everything: drop it (keeps the table = real overrides only).
        $this->db()->table(CategoryCatalog::OVERRIDES)->where('central_category_id', $centralId)
            ->whereNull('active')->whereNull('featured')->whereNull('order_number')->whereNull('thumb_file_id')->delete();
    }

    // ── Membership ──────────────────────────────────────────────────────────

    /**
     * Product form save: the product's categories become exactly $values (keys / refs) among the categories in the
     * merged set. Links to categories outside the set (centrally hidden / out of scope) are never touched (Phase -1:
     * the form does not show them, so saving must keep them).
     *
     * @param  array<int, mixed>  $values
     */
    public function syncProduct(Product $product, array $values): void
    {
        $catalog = app(CategoryCatalog::class);
        $visible = array_flip($catalog->keys());
        $wanted = [];
        foreach ($values as $value) {
            $key = CategoryKey::keyOf($value);
            if ($key !== null && isset($visible[$key])) {
                $wanted[$key] = true;
            }
        }

        $productRef = $this->productRef($product);
        $centralPairs = $productRef->isCentral() ? array_flip(array_map('intval', $this->db()->table(CentralTable::qualified('category_product'))
            ->where('product_id', $productRef->id)->pluck('category_id')->all())) : [];
        $ownLinks = array_flip(array_map('intval', $this->db()->table('category_product as t')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->whereNull('c.central_category_id')
            ->where('t.product_id', $product->id)
            ->pluck('t.category_id')->all()));

        $pairRows = $this->db()->table(CategoryCatalog::PAIR_OVERRIDES)
            ->where('category_type', 'central')->where('product_type', $productRef->source)->where('product_id', $productRef->id)
            ->get()->keyBy('category_id')->all();

        foreach (array_keys($visible) as $key) {
            $ref = CategoryKey::ref($key);
            $want = isset($wanted[$key]);

            if ($ref->isTenant()) {
                if ($want && ! isset($ownLinks[$ref->id])) {
                    $this->db()->table('category_product')->insert([
                        'category_id' => $ref->id, 'product_id' => $product->id, 'source' => 'tenant',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                } elseif (! $want && isset($ownLinks[$ref->id])) {
                    $this->db()->table('category_product')->where('category_id', $ref->id)->where('product_id', $product->id)->delete();
                }

                continue;
            }

            $row = $pairRows[$ref->id] ?? null;
            if (isset($centralPairs[$ref->id])) {
                // Central has the pair: hide / un-hide it (an override keeps its sort).
                if (! $want) {
                    if ($row !== null && $row->hidden && $row->source === 'central') {
                        continue;
                    }
                    $row === null
                        ? $this->insertPair($ref->id, $productRef, ['source' => 'central', 'hidden' => true])
                        : $this->pairQuery($ref->id, $productRef)->update(['source' => 'central', 'hidden' => true, 'updated_at' => now()]);
                } elseif ($row !== null && $row->hidden) {
                    $row->sort_order === null
                        ? $this->pairQuery($ref->id, $productRef)->delete()
                        : $this->pairQuery($ref->id, $productRef)->update(['hidden' => false, 'updated_at' => now()]);
                }
            } elseif ($want && ($row === null || $row->hidden)) {
                $row === null
                    ? $this->insertPair($ref->id, $productRef, ['source' => 'tenant', 'hidden' => false])
                    : $this->pairQuery($ref->id, $productRef)->update(['source' => 'tenant', 'hidden' => false, 'updated_at' => now()]);
            } elseif (! $want && $row !== null && $row->source === 'tenant') {
                $this->pairQuery($ref->id, $productRef)->delete();
            }
        }

        CatalogRefWriter::fillQuietly('category_product', ['product_id' => [$product->id]]);
        $this->bump();
    }

    /**
     * Per-category product order (CategoryProductsController): tenant product ids in display order. Central category:
     * sort overrides on its pairs; own category: `category_product.sort_order`.
     *
     * @param  array<int, mixed>  $productIds
     */
    public function sortProducts(MergedCategory $category, array $productIds): void
    {
        $members = array_flip($category->isCentral() ? app(CategoryCatalog::class)->productIds((int) $category->id) : []);

        foreach (array_values($productIds) as $index => $productId) {
            $productId = (int) $productId;
            if (! $category->isCentral()) {
                $this->db()->table('category_product')->where('category_id', $category->source_id)->where('product_id', $productId)
                    ->update(['sort_order' => $index, 'updated_at' => now()]);

                continue;
            }

            if (! isset($members[$productId]) || ($product = Product::query()->find($productId)) === null) {
                continue;
            }

            $productRef = $this->productRef($product);
            $this->pairQuery((int) $category->source_id, $productRef)->exists()
                ? $this->pairQuery((int) $category->source_id, $productRef)->update(['sort_order' => $index, 'updated_at' => now()])
                : $this->insertPair((int) $category->source_id, $productRef, ['source' => 'central', 'hidden' => false, 'sort_order' => $index]);
        }

        $this->bump();
    }

    /** Typed ref of a tenant product (phase-1 rule: central iff linked and not own). */
    public function productRef(Product $product): CatalogRef
    {
        $own = CatalogRefColumns::has($this->db(), 'products', 'is_own_product') && (bool) $product->getAttribute('is_own_product');

        return ! $own && $product->central_product_id
            ? CatalogRef::central((int) $product->central_product_id)
            : CatalogRef::tenant((int) $product->id);
    }

    private function pairQuery(int $centralCategoryId, CatalogRef $product): \Illuminate\Database\Query\Builder
    {
        return $this->db()->table(CategoryCatalog::PAIR_OVERRIDES)
            ->where('category_type', 'central')->where('category_id', $centralCategoryId)
            ->where('product_type', $product->source)->where('product_id', $product->id);
    }

    private function insertPair(int $centralCategoryId, CatalogRef $product, array $values): void
    {
        $this->db()->table(CategoryCatalog::PAIR_OVERRIDES)->insert($values + [
            'category_type' => 'central', 'category_id' => $centralCategoryId,
            'product_type' => $product->source, 'product_id' => $product->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function bump(): void
    {
        CacheVersion::bumpMany(['Category', 'Product']);
    }
}
