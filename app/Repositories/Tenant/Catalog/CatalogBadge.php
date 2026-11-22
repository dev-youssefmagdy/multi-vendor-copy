<?php

namespace App\Repositories\Tenant\Catalog;

use App\Support\Catalog\CatalogRef;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One badge of the merged badge catalog (dedup plan phase 2): a central badge (read live, read-only for the tenant)
 * or a tenant-own badge. Exposes what the panel views read from a `Tenant\ProductBadge` today:
 *
 *  - `id`   the CatalogRef string ("c:12" / "t:3"), so `<select>` values, `pluck('id')` and posted `badge_ids` carry
 *           the source with them (§3.1); `badgeId` is the numeric id on its side;
 *  - `text`, `active`;
 *  - route key = `text` (like ProductBadge::getRouteKeyName()), so `route('tenant.badges.show', $badge)` is unchanged.
 */
final class CatalogBadge implements UrlRoutable
{
    public readonly string $id;

    public readonly int $badgeId;

    public function __construct(public readonly CatalogRef $ref, public readonly string $text, public readonly bool $active)
    {
        $this->id = (string) $ref;
        $this->badgeId = $ref->id;
    }

    public function isCentral(): bool
    {
        return $this->ref->isCentral();
    }

    /** Central badges are read-only for the tenant (text/active belong to central); assignments are always editable. */
    public function isEditable(): bool
    {
        return ! $this->isCentral();
    }

    public function getRouteKey(): mixed
    {
        return $this->text;
    }

    public function getRouteKeyName(): string
    {
        return 'text';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return null; // resolved explicitly by BadgeCatalog::resolve(), never by implicit binding
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        return null;
    }
}
