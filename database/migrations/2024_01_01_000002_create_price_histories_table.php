<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->enum('resolution', ['1m', '5m', '15m', '1h', '1d']);
            $table->decimal('open', 18, 6);
            $table->decimal('high', 18, 6);
            $table->decimal('low', 18, 6);
            $table->decimal('close', 18, 6);
            $table->decimal('volume', 18, 4)->default(0);
            $table->timestamp('recorded_at');

            // Unique candle per asset+resolution+bucket
            $table->unique(['asset_id', 'resolution', 'recorded_at']);

            // Covering index for chart queries: asset + resolution + time range
            $table->index(['asset_id', 'resolution', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
