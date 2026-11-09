<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // false: bu kategorideki (ve alt kategorilerindeki) ürünler Drive senkronundan etkilenmez
            $table->boolean('sheet_sync')->default(true)->after('is_trend');
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('sheet_sync'));
    }
};
