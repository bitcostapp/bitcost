<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModelPricingSeeder extends Seeder
{
    /**
     * Seed the model pricing table from the committed models.dev catalog.
     */
    public function run(): void
    {
        \Artisan::call('pricing:sync');
    }
}
