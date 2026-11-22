<?php

namespace App\Models\Tenant\Catalog;

use App\Models\Tenant\Catalog\Concerns\MergedFlashSaleBehaviour;
use App\Repositories\Tenant\Catalog\FlashSaleCatalog;

/**
 * A merged flash sale ON one tenant product (FlashSaleCatalog::membershipSql()): the related model of
 * `Tenant\Product::flashSales()` while the tenant's `flash-sales` section is `central`, so eager loads with
 * `activeWindow()->forCountry()`, `whereHas('flashSales')`, activeStorefrontFlashSale() and the themes'
 * `$pricing['flash_sale']->end_date` keep working. `id` = sale ref ("c:12" / "t:3"), `product_id` = tenant product id.
 */
class MergedProductFlashSale extends MergedReadModel
{
    use MergedFlashSaleBehaviour;

    protected $table = 'merged_product_flash_sales';

    protected $keyType = 'string';

    protected static function derivedSql(): string
    {
        return FlashSaleCatalog::membershipSql();
    }
}
