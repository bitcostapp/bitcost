<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model pricing catalog
    |--------------------------------------------------------------------------
    |
    | A committed snapshot of the models.dev catalog (USD per 1,000,000 tokens),
    | used to seed `model_pricings` via `php artisan pricing:sync` and as the
    | runtime fallback when a model has no pricing row. Tests override this path
    | to point at a fixture.
    |
    */

    'catalog_path' => env('PRICING_CATALOG_PATH', database_path('data/model-pricing.json')),
];
