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
        Schema::table('usages', function (Blueprint $table) {
            // The raw CLI-computed turn cost, retained for audit even when the
            // server's own pricing wins.
            $table->decimal('client_cost_total', 20, 10)->nullable()->after('cost_breakdown');
            // Provenance of cost_total: 'pricing' | 'catalog' | 'client' | null.
            $table->string('cost_source')->nullable()->after('is_priced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usages', function (Blueprint $table) {
            $table->dropColumn(['client_cost_total', 'cost_source']);
        });
    }
};
