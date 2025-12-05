<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->onDelete('cascade');
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->decimal('amount_from', 20, 2);
            $table->decimal('amount_to', 20, 2);
            $table->decimal('rate_used', 20, 6);
            $table->timestamps();

            $table->index('telegram_user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_histories');
    }
};

