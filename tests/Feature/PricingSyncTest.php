<?php

use App\Models\ModelPricing;

beforeEach(function () {
    config(['pricing.catalog_path' => base_path('tests/Fixtures/model-pricing.json')]);
});

test('pricing:sync upserts every catalog row into model_pricings', function () {
    $this->artisan('pricing:sync')
        ->expectsOutputToContain('Synced 2 models.')
        ->assertExitCode(0);

    expect(ModelPricing::count())->toBe(2);
    $this->assertDatabaseHas('model_pricings', [
        'provider' => 'anthropic',
        'model' => 'claude-fixture',
        'input_price' => '3.000000',
        'output_price' => '15.000000',
    ]);
});

test('pricing:sync is idempotent and updates changed prices', function () {
    $this->artisan('pricing:sync')->assertExitCode(0);

    // A stale price is corrected on the next sync rather than duplicated.
    ModelPricing::query()
        ->where('provider', 'anthropic')
        ->where('model', 'claude-fixture')
        ->update(['input_price' => 999]);

    $this->artisan('pricing:sync')->assertExitCode(0);

    expect(ModelPricing::count())->toBe(2);
    $this->assertDatabaseHas('model_pricings', [
        'provider' => 'anthropic',
        'model' => 'claude-fixture',
        'input_price' => '3.000000',
    ]);
});
