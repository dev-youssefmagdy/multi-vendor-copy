<?php

/*
 * Dedup plan phase 7 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §6 / §12 "Phase 7").
 */
return [

    /*
     * Copy-column drops (`catalog:dedup-cleanup <section> --drop-schema`, files in
     * database/migrations/tenant_dedup_drops). The code that still reads those columns (sync rollback path,
     * CatalogRefColumns checks, the `sync` branches of the merged repositories) is only removed by the
     * "final removal" commit listed in plan §12. That commit flips this to true; it is deliberately NOT an env
     * value, so ops cannot enable the drops ahead of the code that no longer needs the columns.
     */
    'schema_drops_code_ready' => false,

    /* Backups written by `catalog:dedup-cleanup` before any delete (relative to storage_path()). */
    'backup_path' => 'app/dedup-backups',
];
