<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 20); // percent | fixed | free_shipping
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->json('category_ids')->nullable();     // boşsa tüm ürünler
            $table->boolean('exclude_sale_items')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30); // category_percent | cart_percent | cart_fixed | free_shipping
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->json('category_ids')->nullable();
            $table->string('banner_text')->nullable();     // üst barda duyuru
            $table->string('badge_text', 30)->nullable();  // ürün kartında etiket
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->string('coupon_code', 40)->nullable()->after('discount')->index();
            $table->json('discount_lines')->nullable()->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['discount', 'coupon_code', 'discount_lines']));
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('coupons');
    }
};
