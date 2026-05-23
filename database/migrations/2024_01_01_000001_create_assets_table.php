<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('name', 100);
            $table->enum('unit', ['troy_oz', 'gram', 'kg'])->default('troy_oz');
            $table->char('currency', 3)->default('EUR');
            $table->decimal('spot_price', 18, 6)->default(0);
            $table->decimal('bid_price', 18, 6)->default(0);
            $table->decimal('ask_price', 18, 6)->default(0);
            $table->decimal('spread', 18, 6)->default(0);
            $table->decimal('daily_change', 18, 6)->default(0);
            $table->decimal('daily_change_pct', 10, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
