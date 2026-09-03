<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_gateways')->get()->each(function ($row) {
            $credentials = json_decode($row->credentials ?? '{}', true) ?? [];

            if (!array_key_exists('sandbox', $credentials)) {
                return;
            }

            unset($credentials['sandbox']);

            DB::table('payment_gateways')->where('id', $row->id)->update([
                'credentials' => json_encode($credentials),
            ]);
        });
    }

    public function down(): void
    {
        // Intentionally irreversible — the mode column is the source of truth now.
    }
};
