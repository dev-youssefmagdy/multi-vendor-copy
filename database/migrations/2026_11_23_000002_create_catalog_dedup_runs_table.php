<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan §5a.2: progress of every per-tenant data step (`catalog:backfill-refs`, later `catalog:dedup-migrate`
 * migrate / verify / cleanup). One row per run: tenant, section, step, counts, status, error, timestamps.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('catalog_dedup_runs')) {
            return;
        }

        Schema::create('catalog_dedup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->nullable();
            $table->string('section', 40);                 // refs | badges | products | …
            $table->string('step', 20);                    // dry-run | migrate | verify | cleanup
            $table->string('status', 20)->default('running'); // running | done | failed | skipped
            $table->json('counts')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'section', 'step'], 'catalog_dedup_runs_tenant_section_step_index');
            $table->index(['section', 'status'], 'catalog_dedup_runs_section_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_dedup_runs');
    }
};
