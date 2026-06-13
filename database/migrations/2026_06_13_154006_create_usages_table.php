<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            // Client-supplied turn id; scopes idempotency to a single task.
            $table->string('idempotency_key', 128);
            $table->string('session')->nullable();
            $table->string('request_id')->nullable();
            $table->string('provider');
            $table->string('model');
            $table->string('variant')->nullable();
            $table->unsignedBigInteger('tokens_input')->default(0);
            $table->unsignedBigInteger('tokens_output')->default(0);
            $table->unsignedBigInteger('tokens_reasoning')->default(0);
            $table->unsignedBigInteger('tokens_cache_read')->default(0);
            $table->unsignedBigInteger('tokens_cache_write')->default(0);
            $table->decimal('cost_total', 20, 10)->nullable();
            $table->json('cost_breakdown')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_subscription')->default(false);
            $table->boolean('is_priced')->default(false);
            $table->foreignId('pricing_id')->nullable()->constrained('model_pricings')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'idempotency_key']);
            $table->index('session');
            $table->index(['provider', 'model']);
            $table->index('is_priced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usages');
    }
};
