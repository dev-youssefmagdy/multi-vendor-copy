<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\StaticPage;
use App\Models\Tenant as TenantModel;
use Illuminate\Support\Collection;

final class ComplianceService
{
    /** Returns active compliance pages for display. */
    public function compliancePages(): Collection
    {
        return tenancy()->central(
            fn () => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->orderBy('id')
                ->get(['id', 'slug', 'updated_at'])
                ->map(fn (StaticPage $p) => [
                    'id' => $p->id,
                    'title' => $p->translationValue('title') ?? $p->slug,
                    'content' => $p->translationValue('content') ?? '',
                    'slug' => $p->slug,
                ])
        ) ?? collect();
    }

    /** True if any active compliance pages exist. */
    public function compliancePagesExist(): bool
    {
        return tenancy()->central(
            fn () => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->exists()
        ) ?? false;
    }

    /** Deterministic version string — hash of IDs + latest updated_at. */
    public function complianceVersion(): string
    {
        $rows = tenancy()->central(
            fn () => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->orderBy('id')
                ->get(['id', 'updated_at'])
                ->map(fn (StaticPage $p) => $p->id.':'.$p->updated_at?->timestamp)
                ->implode('|')
        ) ?? '';

        return md5($rows);
    }

    /** True if the tenant has accepted the current compliance version. */
    public function hasAcceptedCurrentCompliance(): bool
    {
        $currentVersion = $this->complianceVersion();

        $accepted = TenantModel::query()->where('id', tenant('id'))->first()?->compliance_version;

        return $accepted === $currentVersion;
    }

    public function accept(): void
    {
        TenantModel::saveData(tenant('id'), [
            'compliance_accepted_at' => now(),
            'compliance_version' => $this->complianceVersion(),
        ]);
    }
}
