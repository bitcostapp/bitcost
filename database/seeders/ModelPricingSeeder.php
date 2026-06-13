<?php

namespace Database\Seeders;

use App\Models\ModelPricing;
use Illuminate\Database\Seeder;

class ModelPricingSeeder extends Seeder
{
    /**
     * Seed the model pricing table from the curated snapshot.
     */
    public function run(): void
    {
        $rows = require database_path('data/model-pricing.php');

        foreach ($rows as $row) {
            ModelPricing::updateOrCreate(
                [
                    'provider' => $row['provider'],
                    'model' => $row['model'],
                    'variant' => $row['variant'] ?? null,
                    'effective_from' => $row['effective_from'] ?? null,
                ],
                $row,
            );
        }
    }
}
