<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // blog_categories: name becomes translated, slug stays for canonical URL
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // blog_posts: title/excerpt/content become translated, slug stays for routing
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['title', 'excerpt', 'content']);
        });

        // faqs: question/answer/category all become translated (no slug on faqs)
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question', 'answer', 'category']);
        });

        // static_pages: title/content become translated, slug stays for routing
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn(['title', 'content']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title')->after('blog_category_id');
            $table->text('excerpt')->nullable()->after('title');
            $table->longText('content')->nullable()->after('excerpt');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question')->after('id');
            $table->text('answer')->nullable()->after('question');
            $table->string('category')->nullable()->after('answer');
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->longText('content')->nullable()->after('slug');
        });
    }
};
