<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Banners, coupons and flash sales can target several countries. A record with
 * NO pivot rows is available in ALL countries (the old NULL country_id).
 * Countries live in the central DB, so there is no FK on country_id.
 */
return new class extends Migration {
    private array $tables = [
        'banners' => ['pivot' => 'banner_country', 'key' => 'banner_id'],
        'coupons' => ['pivot' => 'coupon_country', 'key' => 'coupon_id'],
        'flash_sales' => ['pivot' => 'flash_sale_country', 'key' => 'flash_sale_id'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $cfg) {
            Schema::create($cfg['pivot'], function (Blueprint $t) use ($table, $cfg) {
                $t->unsignedBigInteger($cfg['key']);
                $t->unsignedBigInteger('country_id');
                $t->primary([$cfg['key'], 'country_id']);
                $t->index('country_id');
                $t->foreign($cfg['key'])->references('id')->on($table)->cascadeOnDelete();
            });

            if (Schema::hasColumn($table, 'country_id')) {
                DB::table($table)->whereNotNull('country_id')->orderBy('id')->each(function ($row) use ($cfg) {
                    DB::table($cfg['pivot'])->insertOrIgnore([$cfg['key'] => $row->id, 'country_id' => $row->country_id]);
                });

                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('country_id'));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $cfg) {
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('country_id')->nullable());

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
