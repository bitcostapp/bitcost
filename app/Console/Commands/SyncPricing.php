<?php

namespace App\Console\Commands;

use App\Models\ModelPricing;
use App\Services\ModelCatalog;
use Illuminate\Console\Command;

class SyncPricing extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pricing:sync';

    /**
     * @var string
     */
    protected $description = 'Sync the model_pricings table from the committed models.dev catalog snapshot.';

    /**
     * Upsert every catalog row into the model_pricings table.
     */
    public function handle(ModelCatalog $catalog): int
    {
        $count = 0;

        foreach ($catalog->all() as $row) {
            ModelPricing::updateOrCreate(
                [
                    'provider' => $row['provider'],
                    'model' => $row['model'],
                    'variant' => $row['variant'],
                    'effective_from' => null,
                ],
                [
                    'input_price' => $row['input_price'],
                    'output_price' => $row['output_price'],
                    'cache_read_price' => $row['cache_read_price'],
                    'cache_write_price' => $row['cache_write_price'],
                    'reasoning_price' => $row['reasoning_price'],
                    'currency' => $row['currency'],
                ],
            );

            $count++;
        }

        $this->info("Synced {$count} models.");

        return self::SUCCESS;
    }
}
