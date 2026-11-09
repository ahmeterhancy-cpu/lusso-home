<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'categories', 'pages', 'posts'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 300)->nullable();
            });
        }

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_url');
            $table->unsignedSmallInteger('status')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);              // created | updated | deleted | custom
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('is_admin'); // null = müşteri
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('web')->after('number'); // web | panel
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::table('contact_messages', fn (Blueprint $t) => $t->dropColumn(['reply', 'replied_at']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('redirects');
        foreach (['products', 'categories', 'pages', 'posts'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['meta_title', 'meta_description']));
        }
    }
};
