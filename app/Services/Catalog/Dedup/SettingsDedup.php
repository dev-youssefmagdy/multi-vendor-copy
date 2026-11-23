<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate settings` (dedup plan phase 6): see ConfigDedup. */
final class SettingsDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'settings';
    }
}
