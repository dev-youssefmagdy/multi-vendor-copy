<?php

namespace App\Models\Tenant\Concerns;

use App\Support\Catalog\ConfigOverlayQueryBuilder;
use App\Support\Catalog\ConfigVisibleScope;

/**
 * Dedup plan phase 6: a tenant config row (language, currency, theme, payment gateway, email template, setting, page)
 * shows the CENTRAL-owned columns live from central while its section is `central` (ConfigOverlayQueryBuilder +
 * ConfigCatalog::overlayValues) and is hidden when central dropped its counterpart (ConfigVisibleScope). Models declare
 * the section in configCatalogSection().
 */
trait OverlaysCentralConfig
{
    abstract protected static function configCatalogSection(): string;

    protected static function bootOverlaysCentralConfig(): void
    {
        static::addGlobalScope('config-visible', new ConfigVisibleScope(static::configCatalogSection()));
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return (new ConfigOverlayQueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor()))
            ->forSection(static::configCatalogSection());
    }
}
