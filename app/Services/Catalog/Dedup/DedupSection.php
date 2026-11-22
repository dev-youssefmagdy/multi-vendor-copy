<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;

/**
 * One section handler of `catalog:dedup-migrate` (dedup plan §5a.2). Both methods run INSIDE the tenant
 * (tenancy initialised by DedupRunner) and return a JSON-serialisable report.
 *
 *  - migrate(): ADDITIVE only (override rows, typed rows); with $dryRun nothing is written, the report shows what
 *    would be. Must be idempotent (a second run adds nothing).
 *  - verify(): read-only shadow-read diff between the old sync-table read and the new merged read; the report's
 *    `diffs` (int) must be 0 before the tenant's flag may flip to `central`.
 */
interface DedupSection
{
    public function name(): string;

    /** null = ready; otherwise why this tenant DB cannot be processed yet (e.g. "run tenants:migrate"). */
    public function notReadyReason(): ?string;

    /** @return array<string, mixed> must contain `counts` (array<string, int>) */
    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array;

    /** @return array<string, mixed> must contain `diffs` (int) and `counts` */
    public function verify(Tenant $tenant): array;
}
