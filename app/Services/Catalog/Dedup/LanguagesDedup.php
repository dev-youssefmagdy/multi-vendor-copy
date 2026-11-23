<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate languages` (dedup plan phase 6): see ConfigDedup. */
final class LanguagesDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'languages';
    }
}
