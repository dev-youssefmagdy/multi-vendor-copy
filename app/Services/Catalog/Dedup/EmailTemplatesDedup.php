<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate email_templates` (dedup plan phase 6): see ConfigDedup. */
final class EmailTemplatesDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'email-templates';
    }
}
