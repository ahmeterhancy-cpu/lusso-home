<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Eski WooCommerce kayıtlarıyla eşleşme (aktarım tekrar çalıştırılabilsin). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_id')->nullable()->unique()->after('id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('wc_id'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('wc_id'));
    }
};
