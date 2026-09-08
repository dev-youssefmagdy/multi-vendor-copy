<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'name',
        'slug',
        'email',
        'phone',
        'status',
        'category_ids',
        'primary_language_id',
        'package_id',
        'trial_ends_at',
        'activated_at',
        'compliance_status',
        'compliance_admin_note',
        'compliance_reviewed_by',
        'compliance_reviewed_at',
    ];

    public function up(): void
    {
        // Backfill: the VirtualColumn trait already redirects every Eloquent
        // save of these attributes into `data`, but rows touched only via
        // raw SQL/seeders may still carry their only copy in the physical
        // column. Merge those into `data` (without clobbering existing data
        // keys) before the columns are dropped.
        $rows = DB::table('tenants')->select(array_merge(['id', 'data'], $this->columns))->get();

        foreach ($rows as $row) {
            $data = json_decode($row->data ?? '{}', true) ?: [];
            $changed = false;

            foreach ($this->columns as $column) {
                if (array_key_exists($column, $data)) {
                    continue;
                }

                $value = $row->{$column};
                if ($value === null) {
                    continue;
                }

                $data[$column] = $value;
                $changed = true;
            }

            if ($changed) {
                DB::table('tenants')->where('id', $row->id)->update([
                    'data' => json_encode($data),
                ]);
            }
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['primary_language_id']);
            $table->dropForeign(['package_id']);
            $table->dropColumn($this->columns);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('email')->nullable()->after('slug');
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('onboarding')->after('phone');
            $table->foreignId('primary_language_id')->nullable()->after('status')->constrained('languages')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->after('primary_language_id')->constrained('packages')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('package_id');
            $table->timestamp('activated_at')->nullable()->after('trial_ends_at');
            $table->json('category_ids')->nullable()->after('activated_at');
            $table->string('compliance_status')->default('pending')->after('data');
            $table->text('compliance_admin_note')->nullable()->after('compliance_status');
            $table->string('compliance_reviewed_by')->nullable()->after('compliance_admin_note');
            $table->timestamp('compliance_reviewed_at')->nullable()->after('compliance_reviewed_by');
        });

        $rows = DB::table('tenants')->select(['id', 'data'])->get();
        foreach ($rows as $row) {
            $data = json_decode($row->data ?? '{}', true) ?: [];
            $update = array_intersect_key($data, array_flip($this->columns));
            if ($update !== []) {
                DB::table('tenants')->where('id', $row->id)->update($update);
            }
        }
    }
};
