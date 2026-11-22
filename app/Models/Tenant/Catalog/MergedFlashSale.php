<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\Catalog\Concerns\MergedFlashSaleBehaviour;
use App\Repositories\Tenant\Catalog\FlashSaleCatalog;

/**
 * One merged flash sale (FlashSaleCatalog::salesSql(): live central sale with ≥ 1 carried product ∪ own sale), used for
 * the storefront strips, mobile home and the panel list while the tenant's `flash-sales` section is `central`.
 * `id` = ref string ("c:12" / "t:3"); `products` is set by FlashSaleCatalog::withProducts(). Read-only.
 */
class MergedFlashSale extends MergedReadModel
{
    use MergedFlashSaleBehaviour;

    protected $table = 'merged_flash_sales';

    protected $keyType = 'string';

    protected $appends = ['banner_url'];

    protected static function derivedSql(): string
    {
        return FlashSaleCatalog::salesSql();
    }
}
