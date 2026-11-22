<?php

namespace App\Models\Tenant\Catalog;

use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Support\Catalog\CatalogRef;

/**
 * One merged product↔badge membership row (BadgeCatalog::membershipSql()), the related model of
 * `Tenant\Product::badges()` while the tenant's `badges` section is `central`. Exposes what themes/panels read from
 * a `Tenant\ProductBadge` (`text`, `active`), plus `id` = badge ref string ("c:12"/"t:3"), `badge_id`,
 * `country_id`, `sort_order`, `pair_source` (central pivot / tenant pivot).
 *
 * @property string $id
 * @property string $badge_type
 * @property int $badge_id
 * @property string $text
 * @property bool $active
 * @property int $product_id
 * @property int|null $country_id
 * @property int|null $sort_order
 */
class MergedProductBadge extends MergedReadModel
{
    protected $table = 'merged_product_badges';

    protected $keyType = 'string';

    protected static function derivedSql(): string
    {
        return BadgeCatalog::membershipSql();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'badge_id' => 'integer',
            'product_id' => 'integer',
            'catalog_product_id' => 'integer',
            'country_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function ref(): CatalogRef
    {
        return CatalogRef::make($this->badge_type, $this->badge_id);
    }
}
