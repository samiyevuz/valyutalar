<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('language', ['en', 'ru', 'uz'])->default('en');
            $table->json('favorite_currencies')->nullable();
            $table->boolean('daily_digest_enabled')->default(false);
            $table->time('digest_time')->default('09:00:00');
            $table->string('state')->nullable();
            $table->json('state_data')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('telegram_id');
            $table->index('language');
            $table->index('daily_digest_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};

