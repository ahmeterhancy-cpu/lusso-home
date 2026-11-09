<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ürün XML akışları (Google Merchant / Meta kataloğu, bayi-pazar yeri XML'i). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xml_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->string('format', 20)->default('google'); // google | genel
            $table->json('category_ids')->nullable();          // boş = tüm kategoriler
            $table->boolean('include_children')->default(true);
            $table->boolean('in_stock_only')->default(false);
            $table->boolean('priced_only')->default(true);
            $table->string('brand', 120)->nullable();
            $table->string('google_category', 200)->nullable();
            $table->string('token', 64)->nullable();            // doluysa ?key= ister
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xml_feeds');
    }
};
