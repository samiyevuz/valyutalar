<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->onDelete('cascade');
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->enum('condition', ['above', 'below']);
            $table->decimal('target_rate', 20, 6);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_triggered')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->decimal('triggered_rate', 20, 6)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_triggered']);
            $table->index('currency_from');
            $table->index('currency_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

