<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('return_policy_override')->default(false)->after('order_number');
            $table->boolean('is_returnable')->default(true)->after('return_policy_override');
            $table->unsignedSmallInteger('return_window_days')->nullable()->after('is_returnable');
            $table->decimal('return_fee', 8, 2)->nullable()->after('return_window_days');
            $table->boolean('return_video_required')->default(false)->after('return_fee');
            $table->text('return_conditions')->nullable()->after('return_video_required');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'return_policy_override',
                'is_returnable',
                'return_window_days',
                'return_fee',
                'return_video_required',
                'return_conditions',
            ]);
        });
    }
};
