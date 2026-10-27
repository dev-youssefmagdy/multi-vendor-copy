<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central coupons, flash sales and default banners can target several
 * countries. A record with NO pivot rows is available in ALL countries
 * (the old NULL country_id).
 */
return new class extends Migration {
    private array $tables = [
        'central_coupons' => ['pivot' => 'central_coupon_country', 'key' => 'central_coupon_id'],
        'central_flash_sales' => ['pivot' => 'central_flash_sale_country', 'key' => 'central_flash_sale_id'],
        'default_banners' => ['pivot' => 'default_banner_country', 'key' => 'default_banner_id'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $cfg) {
            Schema::create($cfg['pivot'], function (Blueprint $t) use ($table, $cfg) {
                $t->unsignedBigInteger($cfg['key']);
                $t->unsignedBigInteger('country_id');
                $t->primary([$cfg['key'], 'country_id']);
                $t->foreign($cfg['key'])->references('id')->on($table)->cascadeOnDelete();
                $t->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            });

            if (Schema::hasColumn($table, 'country_id')) {
                DB::table($table)->whereNotNull('country_id')->orderBy('id')->each(function ($row) use ($cfg) {
                    DB::table($cfg['pivot'])->insertOrIgnore([$cfg['key'] => $row->id, 'country_id' => $row->country_id]);
                });

                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['country_id']);
                    $t->dropColumn('country_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $cfg) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('country_id')->nullable();
                $t->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            });

            // Only a single-country record can be represented by the old column.
            DB::table($cfg['pivot'])->select($cfg['key'])->groupBy($cfg['key'])->havingRaw('COUNT(*) = 1')
                ->pluck($cfg['key'])->each(function ($id) use ($table, $cfg) {
                    DB::table($table)->where('id', $id)->update([
                        'country_id' => DB::table($cfg['pivot'])->where($cfg['key'], $id)->value('country_id'),
                    ]);
                });

            Schema::dropIfExists($cfg['pivot']);
        }
    }
};
