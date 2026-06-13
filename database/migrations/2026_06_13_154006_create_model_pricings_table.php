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
        Schema::create('model_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model');
            $table->string('variant')->nullable();
            // Prices are USD per 1,000,000 tokens, matching the CLI's models.dev units.
            $table->decimal('input_price', 16, 6)->default(0);
            $table->decimal('output_price', 16, 6)->default(0);
            $table->decimal('cache_read_price', 16, 6)->nullable();
            $table->decimal('cache_write_price', 16, 6)->nullable();
            $table->decimal('reasoning_price', 16, 6)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_subscription')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'model', 'variant', 'effective_from']);
            $table->index(['provider', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_pricings');
    }
};
