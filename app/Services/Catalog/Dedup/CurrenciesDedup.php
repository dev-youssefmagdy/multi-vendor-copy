<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate currencies` (dedup plan phase 6): see ConfigDedup. */
final class CurrenciesDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'currencies';
    }
}
