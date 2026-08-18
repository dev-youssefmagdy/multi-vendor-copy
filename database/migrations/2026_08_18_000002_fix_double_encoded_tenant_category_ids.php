<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A legacy model mutator used to json_encode() category_ids before
        // VirtualColumn stored it inside `data`, leaving `data->category_ids`
        // holding a JSON-encoded string instead of a plain array. Unwrap it.
        $rows = DB::table('tenants')->select(['id', 'data'])->get();

        foreach ($rows as $row) {
            $data = json_decode($row->data ?? '{}', true) ?: [];

            if (!array_key_exists('category_ids', $data)) {
                continue;
            }

            $value = $data['category_ids'];
            $changed = false;

            while (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $value = null;
                    break;
                }
                $value = $decoded;
                $changed = true;
            }

            if ($changed) {
                $data['category_ids'] = filled($value) ? array_values((array) $value) : null;
                DB::table('tenants')->where('id', $row->id)->update([
                    'data' => json_encode($data),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible: original encoding depth is not recoverable.
    }
};
