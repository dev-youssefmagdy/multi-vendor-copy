<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Catalog\CatalogRefWriter;
use App\Support\Catalog\CategoryKey;
use Closure;

/**
 * The "Categories" picker of the tenant panel product forms (catalog product + own product), in both modes:
 *
 *  - sync mode (default): options = TenantPanelRepository::categoryTreeOptions() (synced rows), values = tenant ids,
 *    rule `exists:categories,id`, save = `$product->categories()->sync()` + the centrally hidden links + typed-ref
 *    fill (unchanged behaviour);
 *  - `catalog_source.categories = central` (dedup plan phase 4): options = CategoryCatalog::treeOptions() (values =
 *    CategoryKey integers; refs are accepted too), rule = the value is in the merged set, save =
 *    CategoryAssignments::syncProduct() (hidden / out-of-scope links are never touched).
 *
 * Views keep reading `$product->categories->pluck('id')` (merged rows expose the key as `id`).
 */
final class ProductCategorySelection
{
    public function __construct(private readonly CategoryCatalog $catalog, private readonly CategoryAssignments $assignments)
    {
    }

    /** @return list<array{id:int,name:string,depth:int}> */
    public function options(): array
    {
        return CategoryCatalog::enabled() ? $this->catalog->treeOptions() : app(TenantPanelRepository::class)->categoryTreeOptions();
    }

    /** Validation rules for one `category_ids.*` entry. */
    public function itemRules(bool $withExists = true): array
    {
        if (! CategoryCatalog::enabled()) {
            return $withExists ? ['integer', 'exists:categories,id'] : ['integer'];
        }

        return [function (string $attribute, mixed $value, Closure $fail) {
            if (CategoryKey::parse($value) === null || $this->catalog->find($value) === null) {
                $fail(__('validation.exists', ['attribute' => __('categories')]));
            }
        }];
    }

    /** @param  array<int, mixed>  $values  posted `category_ids` */
    public function save(Product $product, array $values): void
    {
        $values = array_values(array_filter($values, fn ($id) => filled($id)));

        if (CategoryCatalog::enabled()) {
            $this->assignments->syncProduct($product, $values);

            return;
        }

        // New links default to category_product.source = tenant; existing links keep their source. Links to categories
        // central currently hides are not in the form, so keep them (dedup plan R1/R2).
        $product->categories()->sync(array_merge($values, app(TenantPanelService::class)->centrallyHiddenCategoryIds($product)));
        CatalogRefWriter::fillQuietly('category_product', ['product_id' => [$product->id]]);
    }
}
