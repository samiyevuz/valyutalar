<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_rates', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 50);
            $table->string('bank_name');
            $table->string('currency_code', 3);
            $table->decimal('buy_rate', 20, 6);
            $table->decimal('sell_rate', 20, 6);
            $table->date('rate_date');
            $table->timestamps();

            $table->unique(['bank_code', 'currency_code', 'rate_date']);
            $table->index('rate_date');
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_rates');
    }
};

