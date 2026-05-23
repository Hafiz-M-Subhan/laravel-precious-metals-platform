<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['market', 'limit'])->default('market');
            $table->decimal('quantity', 18, 6);
            $table->decimal('price_per_unit', 18, 6);
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'processing', 'filled', 'cancelled', 'failed'])
                  ->default('pending');
            $table->timestamp('filled_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['asset_id', 'status', 'filled_at']);
            // Fast lookup of pending orders for the queue worker
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
