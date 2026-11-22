<?php

namespace App\Models\Tenant\Catalog;

use App\Repositories\Tenant\Catalog\ProductCatalog;

/** Merged variant translation rows (ProductCatalog::variantTranslationsSql()). Read-only. */
class MergedProductVariantTranslation extends MergedProductTranslation
{
    protected $table = 'merged_product_variant_translations';

    protected function derivedSql(): string
    {
        return ProductCatalog::variantTranslationsSql();
    }
}
