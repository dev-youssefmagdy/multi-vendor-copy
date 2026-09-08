<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── 1. packages ──────────────────────────────────────────────────────
        // Replace the all_catalogs boolean + package_catalogs pivot with a
        // single categories_count integer: 0 = unlimited, N = max root categories.
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('categories_count')
                ->default(0)
                ->after('features')
                ->comment('0 = unlimited (all categories); N = max parent categories tenant may choose at registration');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('all_catalogs');
        });

        if (Schema::hasTable('package_catalogs')) {
            Schema::drop('package_catalogs');
        }

        // ── 2. tenants ───────────────────────────────────────────────────────
        // Replace catalog_id FK + all_catalogs bool with category_ids JSON.
        // Empty / null category_ids means "all categories" (unlimited plan).
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('category_ids')
                ->nullable()
                ->after('id')
                ->comment('Root central category IDs this tenant is restricted to; null/empty = all categories');
        });

        Schema::table('tenants', function (Blueprint $table) {
            // dropConstrainedForeignId drops the FK index, FK constraint, and the column.
            $table->dropConstrainedForeignId('catalog_id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('all_catalogs');
        });

        // ── 3. pending_registrations ─────────────────────────────────────────
        // Store chosen category IDs in the pending record so they can be
        // applied when the user completes their registration.
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->json('category_ids')
                ->nullable()
                ->after('package_id')
                ->comment('Root category IDs chosen during registration; null = determined by package');
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['catalog_id', 'all_catalogs']);
        });
    }

    public function down(): void
    {
        // ── pending_registrations ────────────────────────────────────────────
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn('category_ids');
            $table->unsignedBigInteger('catalog_id')->nullable()->after('package_id');
            $table->boolean('all_catalogs')->default(true)->after('catalog_id');
        });

        // ── tenants ──────────────────────────────────────────────────────────
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('category_ids');
            $table->foreignId('catalog_id')->nullable()->after('id')
                ->constrained('catalogs')->nullOnDelete();
            $table->boolean('all_catalogs')->default(false)->after('catalog_id');
        });

        // ── packages ─────────────────────────────────────────────────────────
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('categories_count');
            $table->boolean('all_catalogs')->default(true)->after('features');
        });

        Schema::create('package_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->unique(['package_id', 'catalog_id']);
        });
    }
};
