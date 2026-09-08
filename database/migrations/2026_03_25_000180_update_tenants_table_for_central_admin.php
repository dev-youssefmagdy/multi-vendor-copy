<?php

use App\Enums\TenantStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('email')->nullable()->after('slug');
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default(TenantStatus::Onboarding->value)->after('phone');
            $table->foreignId('primary_language_id')->nullable()->after('status')->constrained('languages')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->after('primary_language_id')->constrained('packages')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('package_id');
            $table->timestamp('activated_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropConstrainedForeignId('primary_language_id');
            $table->dropColumn([
                'name',
                'slug',
                'email',
                'phone',
                'status',
                'trial_ends_at',
                'activated_at',
            ]);
        });
    }
};