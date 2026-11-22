<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Concerns\ProductLabelText;
use App\Repositories\Tenant\Catalog\LabelCatalog;

/**
 * A central label on a product the tenant carries (LabelCatalog::membershipSql()), the related model of
 * `Tenant\Product::labels()` while the tenant's `labels` section is `central` (dedup verdict DROP: read central
 * directly, no tenant copy). Same attributes/methods as `Tenant\ProductLabel` (`text`, `translations`, `icon`,
 * `active`, `sort_order`, localizedText(), resolveText()); `id` = central label id.
 */
class MergedProductLabel extends MergedReadModel
{
    use ProductLabelText;

    protected $table = 'merged_product_labels';

    protected static function derivedSql(): string
    {
        return LabelCatalog::membershipSql();
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'translations' => 'array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
