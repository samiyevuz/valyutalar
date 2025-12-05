<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->string('base_currency', 3)->default('UZS');
            $table->decimal('rate', 20, 6);
            $table->string('source')->default('cbu');
            $table->date('rate_date');
            $table->timestamps();

            $table->unique(['currency_code', 'base_currency', 'rate_date', 'source'], 'currency_rates_unique');
            $table->index('rate_date');
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};

