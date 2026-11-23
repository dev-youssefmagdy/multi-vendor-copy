<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate themes` (dedup plan phase 6): see ConfigDedup. */
final class ThemesDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'themes';
    }
}
