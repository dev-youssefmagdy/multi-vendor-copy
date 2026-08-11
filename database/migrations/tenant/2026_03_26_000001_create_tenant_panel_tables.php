<?php

use App\Enums\FileStorageType;
use App\Enums\FileType;
use App\Enums\LanguageDirection;
use App\Enums\OrderStatus;
use App\Enums\PackageTerm;
use App\Enums\PaymentGatewayMode;
use App\Enums\Tenant\CouponType;
use App\Enums\Tenant\SettingType;
use App\Enums\Tenant\SubscriptionGatewayType;
use App\Enums\Tenant\SubscriptionStatus;
use App\Enums\WalletTransactionDirection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_language_id')->nullable()->index();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->string('direction')->default(LanguageDirection::Ltr->value);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->morphs('translatable');
            $table->string('field');
            $table->longText('value');
            $table->timestamps();
            $table->unique(['language_id', 'translatable_type', 'translatable_id', 'field'], 'tenant_translations_unique_field_per_language');
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable()->index();
            $table->string('path');
            $table->string('storage_type')->default(FileStorageType::Public ->value);
            $table->string('file_type')->default(FileType::Image->value);
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->nullableMorphs('model');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_category_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug');
            $table->unsignedInteger('order_number')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false);
            $table->timestamps();
            $table->unique(['central_category_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_product_id')->nullable()->index();
            $table->string('slug')->nullable()->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('central_product_variant_id')->nullable()->index();
            $table->json('option_ids')->nullable();
            $table->decimal('real_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->integer('stock')->nullable();
            $table->decimal('margin_percentage', 6, 2)->default(0);
            $table->string('thumbnail_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_address')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->boolean('paid')->default(false);
            $table->json('payment_details')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('type')->default(WalletTransactionDirection::Credit->value);
            $table->nullableMorphs('model');
            $table->decimal('admin_profit', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default(SubscriptionStatus::Trial->value);
            $table->string('term')->default(PackageTerm::Monthly->value);
            $table->string('payment_gateway_type')->default(SubscriptionGatewayType::Central->value);
            $table->unsignedBigInteger('payment_gateway_id')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_theme_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('preview_path')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('value')->nullable();
            $table->string('type')->default(SettingType::String->value);
            $table->json('options')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default(CouponType::Fixed->value);
            $table->decimal('value', 10, 2)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('minimum_spend', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 6, 2)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_currency_id')->nullable()->index();
            $table->string('code', 6)->unique();
            $table->string('name');
            $table->string('symbol', 12)->nullable();
            $table->decimal('conversion_rate', 20, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_payment_gateway_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(false);
            $table->string('mode')->default(PaymentGatewayMode::Test->value);
            $table->json('required_keys')->nullable();
            $table->json('required_values')->nullable();
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('flash_sales');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('order_activities');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('files');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};
