<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('iso2', 2)->nullable()->after('id')->unique();
            $table->string('iso3', 3)->nullable()->after('iso2')->unique();
            $table->string('currency_code', 6)->nullable()->after('iso3')->index();
            $table->string('language_code', 12)->nullable()->after('currency_code');
            $table->string('language_direction', 3)->nullable()->default('ltr')->after('language_code');
            $table->string('phone_code', 10)->nullable()->after('language_direction');
            $table->string('flag_emoji', 10)->nullable()->after('phone_code');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['currency_code']);
            $table->dropColumn([
                'iso2',
                'iso3',
                'currency_code',
                'language_code',
                'language_direction',
                'phone_code',
                'flag_emoji',
            ]);
        });
    }
};
