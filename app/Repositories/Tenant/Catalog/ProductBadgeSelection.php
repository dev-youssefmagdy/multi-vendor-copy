<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Support\Catalog\CatalogRefWriter;
use Closure;
use Illuminate\Support\Collection;

/**
 * The "Badges" picker of the tenant panel product forms (catalog product + own product), in both modes:
 *
 *  - sync mode (default): options = active synced `product_badges` rows, values = numeric ids, save =
 *    `$product->badges()->sync()` + typed-ref fill (unchanged behaviour);
 *  - `catalog_source.badges = central` (dedup plan phase 2): options = BadgeCatalog::all(active) (central + own),
 *    values = refs ("c:12"/"t:3"), save = BadgeAssignments::syncProduct().
 *
 * Views keep reading `$badge->id` / `$badge->text` and `$product->badges->pluck('id')` (merged rows expose the ref
 * as `id`).
 */
final class ProductBadgeSelection
{
    public function __construct(private readonly BadgeCatalog $catalog, private readonly BadgeAssignments $assignments)
    {
    }

    /** @return Collection<int, ProductBadge|CatalogBadge> */
    public function options(): Collection
    {
        return BadgeCatalog::enabled()
            ? $this->catalog->all(activeOnly: true)
            : ProductBadge::query()->where('active', true)->orderBy('text')->get();
    }

    /** Validation rules for one `badge_ids.*` entry. */
    public function itemRules(bool $withExists = true): array
    {
        if (! BadgeCatalog::enabled()) {
            return $withExists ? ['integer', 'exists:product_badges,id'] : ['integer'];
        }

        return ['string', function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value) || $this->catalog->find($value) === null) {
                $fail(__('validation.exists', ['attribute' => __('badges')]));
            }
        }];
    }

    /** @param  array<int, mixed>  $values  posted `badge_ids` */
    public function save(Product $product, array $values): void
    {
        $values = array_values(array_filter($values, fn ($id) => filled($id)));

        if (BadgeCatalog::enabled()) {
            $this->assignments->syncProduct($product, $values);

            return;
        }

        $product->badges()->sync($values);
        CatalogRefWriter::fillQuietly('product_badge_product', ['product_id' => [$product->id]]);
        // Cache versions: bumped by the caller (as before); BadgeAssignments bumps itself.
    }
}
