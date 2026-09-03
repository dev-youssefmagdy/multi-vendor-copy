<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'languages',
            'categories',
            'variations',
            'variation_options',
            'packages',
            'products',
            'faqs',
            'static_pages',
            'blog_categories',
            'blog_posts',
            'catalogs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'sort_order')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('sort_order');
                });
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'languages' => 0,
            'categories' => 0,
            'variations' => 0,
            'variation_options' => 0,
            'packages' => 0,
            'products' => 0,
            'faqs' => 0,
            'static_pages' => 0,
            'blog_categories' => 0,
            'blog_posts' => 0,
            'catalogs' => 0,
        ];

        foreach ($columns as $tableName => $default) {
            Schema::table($tableName, function (Blueprint $table) use ($default) {
                $table->unsignedInteger('sort_order')->default($default);
            });
        }
    }
};
