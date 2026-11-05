<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central: one optional white-label mobile app per tenant (Phase 8 of application/PUBLISH_TO_STORES.md).
 * Feeds /.well-known/assetlinks.json + apple-app-site-association on the tenant's domains, the /app landing
 * page and the mobile payment return scheme. Edited only in the central admin (Tenants > Edit > Mobile App).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_mobile_apps', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('android_package', 150)->nullable();
            // List of "AA:BB:..." uppercase SHA-256 certificate fingerprints (Play app signing key + upload key).
            $table->json('android_sha256')->nullable();
            $table->string('ios_team_id', 10)->nullable();
            $table->string('ios_bundle_id', 155)->nullable();
            // Unique: two installed apps sharing a scheme would steal each other's payment returns.
            $table->string('url_scheme', 64)->nullable()->unique();
            $table->string('play_store_url', 500)->nullable();
            $table->string('app_store_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_mobile_apps');
    }
};
