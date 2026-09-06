<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `credentials` cast is being switched to `encrypted:array` on the
     * model, which serializes to an encrypted string — the column can no
     * longer be JSON-typed (MySQL rejects non-JSON values), so it's widened
     * to TEXT first. Existing rows were written as plain JSON; encrypt the
     * raw column value directly via Crypt and write it back through the
     * query builder — going through Eloquent here would fail, because
     * dirty checking on an encrypted cast tries to decrypt the (plaintext)
     * original value to compare it against the new one.
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table): void {
            $table->text('credentials')->nullable()->change();
        });

        DB::table('payment_gateways')->select('id', 'credentials')->orderBy('id')->each(function (object $row): void {
            $decoded = json_decode((string) $row->credentials, true) ?? [];

            DB::table('payment_gateways')
                ->where('id', $row->id)
                ->update(['credentials' => Crypt::encryptString(json_encode($decoded))]);
        });
    }

    public function down(): void
    {
        // Encryption is a cast-level concern; column width is not reverted.
    }
};
