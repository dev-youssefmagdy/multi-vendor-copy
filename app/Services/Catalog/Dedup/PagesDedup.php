<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate pages` (dedup plan phase 6): see ConfigDedup. */
final class PagesDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'pages';
    }
}
