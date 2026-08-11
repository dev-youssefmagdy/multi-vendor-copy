<?php

use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default(PackageStatus::Draft->value);
            $table->string('term')->default(PackageTerm::Monthly->value);
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('trial_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
