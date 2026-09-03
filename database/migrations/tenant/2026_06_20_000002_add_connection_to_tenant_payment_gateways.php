<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('connection_status', 20)->nullable()->after('mode');
        });

        // Strip the legacy `sandbox` boolean out of required_keys / required_values.
        // Mode is now fully governed by the `mode` column.
        DB::table('payment_gateways')->get()->each(function ($row) {
            $keys   = json_decode($row->required_keys ?? '[]', true) ?? [];
            $values = json_decode($row->required_values ?? '{}', true) ?? [];

            if (!in_array('sandbox', $keys, true)) {
                return;
            }

            $keys   = array_values(array_filter($keys, fn($k) => $k !== 'sandbox'));
            unset($values['sandbox']);

            DB::table('payment_gateways')->where('id', $row->id)->update([
                'required_keys'   => json_encode(array_values($keys)),
                'required_values' => json_encode($values),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('connection_status');
        });
    }
};
