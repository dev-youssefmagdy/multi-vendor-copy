<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;

/**
 * Exception-safe tenant switching for services that are called from both the tenant and the
 * central context (RETURN_EXCHANGE_REFUND_PLAN.md global rule: never leave tenancy switched on).
 */
trait RunsInTenant
{
    /**
     * Run $callback in $tenant's context and restore the previous context afterwards — also on
     * exceptions (unlike $tenant->run()). No context switch when $tenant is already current.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function inTenant(Tenant $tenant, Closure $callback): mixed
    {
        $previous = tenant();

        if ($previous && $previous->getTenantKey() === $tenant->getTenantKey()) {
            return $callback();
        }

        tenancy()->initialize($tenant);

        try {
            return $callback();
        } finally {
            $previous ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    /** The current tenant when it has this id, otherwise a fresh lookup (null when unknown). */
    protected function findTenant(string $tenantId): ?Tenant
    {
        $current = tenant();

        if ($current instanceof Tenant && (string) $current->getTenantKey() === $tenantId) {
            return $current;
        }

        return Tenant::query()->find($tenantId);
    }
}
