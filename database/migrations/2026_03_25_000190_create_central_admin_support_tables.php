<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('permissions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable()->constrained('admin_roles')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // Schema::create('orders', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('order_number')->unique();
        //     $table->string('tenant_id')->nullable()->index();
        //     $table->string('customer_name')->nullable();
        //     $table->string('customer_email')->nullable();
        //     $table->decimal('total_amount', 12, 2)->default(0);
        //     $table->string('payment_status')->default('pending');
        //     $table->string('status')->default('pending');
        //     $table->string('shipping_status')->default('pending');
        //     $table->timestamp('placed_at')->nullable();
        //     $table->timestamps();
        // });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('gateway');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('domain_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('domain')->index();
            $table->string('status')->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Schema::create('wallets', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('tenant_id')->nullable()->index();
        //     $table->string('currency', 3)->default('USD');
        //     $table->decimal('balance', 12, 2)->default(0);
        //     $table->decimal('pending_balance', 12, 2)->default(0);
        //     $table->string('status')->default('active');
        //     $table->timestamps();
        // });

        // Schema::create('wallet_transactions', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
        //     $table->string('reference')->nullable()->index();
        //     $table->string('direction')->default('credit');
        //     $table->decimal('amount', 12, 2)->default(0);
        //     $table->string('status')->default('posted');
        //     $table->timestamps();
        // });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version')->nullable();
            $table->string('author')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->foreignId('preview_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('inactive');
            $table->string('mode')->default('test');
            $table->json('credentials')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->string('message')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('default_language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sign', 12)->nullable();
            $table->decimal('conversion_rate', 20, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('maintenance_windows');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('domain_requests');
        Schema::dropIfExists('static_pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('admin_roles');
    }
};
