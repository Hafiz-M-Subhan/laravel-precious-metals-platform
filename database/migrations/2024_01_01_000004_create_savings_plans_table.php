<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_per_cycle', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->enum('frequency', ['monthly', 'biweekly', 'weekly'])->default('monthly');
            $table->unsignedTinyInteger('execution_day')->default(1); // 1-28
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->decimal('total_invested', 15, 2)->default(0);
            $table->decimal('total_quantity', 18, 6)->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'next_execution_at']); // For the scheduler query
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_plans');
    }
};
