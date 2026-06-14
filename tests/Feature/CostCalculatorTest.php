<?php

use App\Models\ModelPricing;
use App\Services\CostCalculator;

function tokens(array $overrides = []): array
{
    return array_replace(
        ['input' => 0, 'output' => 0, 'reasoning' => 0, 'cache_read' => 0, 'cache_write' => 0],
        $overrides,
    );
}

test('it computes cost as the sum of tokens times per-million price', function () {
    ModelPricing::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-x',
        'input_price' => 3.0,
        'output_price' => 15.0,
        'cache_read_price' => 0.3,
        'cache_write_price' => 3.75,
        'reasoning_price' => 0.0,
    ]);

    $result = app(CostCalculator::class)->compute('anthropic', 'claude-x', null, tokens([
        'input' => 1_000_000,
        'output' => 1_000_000,
        'cache_read' => 1_000_000,
    ]));

    expect($result['is_priced'])->toBeTrue();
    // 3 + 15 + 0.3 = 18.3
    expect($result['cost_total'])->toBe(18.3);
    expect($result['pricing_id'])->not->toBeNull();
});

test('an unknown model is unpriced', function () {
    $result = app(CostCalculator::class)->compute('nobody', 'nothing', null, tokens(['input' => 500]));

    expect($result['is_priced'])->toBeFalse();
    expect($result['cost_total'])->toBeNull();
    expect($result['pricing_id'])->toBeNull();
});

test('a subscription model produces a flagged notional cost', function () {
    ModelPricing::factory()->subscription()->create([
        'provider' => 'anthropic',
        'model' => 'claude-max',
        'input_price' => 3.0,
        'output_price' => 15.0,
    ]);

    $result = app(CostCalculator::class)->compute('anthropic', 'claude-max', null, tokens(['input' => 1_000_000]));

    expect($result['is_subscription'])->toBeTrue();
    expect($result['cost_breakdown']['notional'])->toBeTrue();
    expect($result['cost_total'])->toBe(3.0);
});

test('a variant-specific price is preferred over the base model', function () {
    ModelPricing::factory()->create([
        'provider' => 'anthropic', 'model' => 'claude-v', 'variant' => null, 'input_price' => 3.0,
    ]);
    ModelPricing::factory()->create([
        'provider' => 'anthropic', 'model' => 'claude-v', 'variant' => 'thinking', 'input_price' => 6.0,
    ]);

    $result = app(CostCalculator::class)->compute('anthropic', 'claude-v', 'thinking', tokens(['input' => 1_000_000]));

    expect($result['cost_total'])->toBe(6.0);
});

test('it prices a model from the catalog when no pricing row exists', function () {
    config(['pricing.catalog_path' => base_path('tests/Fixtures/model-pricing.json')]);

    $result = app(CostCalculator::class)->compute('anthropic', 'claude-fixture', null, tokens([
        'input' => 1000,
        'output' => 500,
    ]));

    // Catalog rates 3/15 → 1000*3/1e6 + 500*15/1e6 = 0.0105
    expect($result['is_priced'])->toBeTrue()
        ->and($result['cost_total'])->toEqualWithDelta(0.0105, 1e-9)
        ->and($result['currency'])->toBe('USD')
        // Priced from the catalog, not a model_pricings row.
        ->and($result['pricing_id'])->toBeNull();
});

test('a database pricing row takes precedence over the catalog', function () {
    config(['pricing.catalog_path' => base_path('tests/Fixtures/model-pricing.json')]);

    $pricing = ModelPricing::factory()->create([
        'provider' => 'anthropic', 'model' => 'claude-fixture', 'variant' => null,
        'input_price' => 10.0, 'output_price' => 20.0,
        'cache_read_price' => 0.0, 'cache_write_price' => 0.0, 'reasoning_price' => 0.0,
    ]);

    $result = app(CostCalculator::class)->compute('anthropic', 'claude-fixture', null, tokens([
        'input' => 1000,
        'output' => 500,
    ]));

    // DB rates 10/20 (not the catalog's 3/15) → 1000*10/1e6 + 500*20/1e6 = 0.02
    expect($result['cost_total'])->toBe(0.02)
        ->and($result['pricing_id'])->toBe($pricing->id);
});

test('a model in neither the table nor the catalog is unpriced', function () {
    config(['pricing.catalog_path' => base_path('tests/Fixtures/model-pricing.json')]);

    $result = app(CostCalculator::class)->compute('anthropic', 'mystery-model', null, tokens(['input' => 1000]));

    expect($result['is_priced'])->toBeFalse()
        ->and($result['cost_total'])->toBeNull()
        ->and($result['pricing_id'])->toBeNull();
});

test('it reports the source of the price it used', function () {
    config(['pricing.catalog_path' => base_path('tests/Fixtures/model-pricing.json')]);

    ModelPricing::factory()->create([
        'provider' => 'anthropic', 'model' => 'claude-db', 'variant' => null, 'input_price' => 3.0,
    ]);

    // model_pricings row → 'pricing'
    expect(app(CostCalculator::class)->compute('anthropic', 'claude-db', null, tokens(['input' => 1]))['source'])
        ->toBe('pricing');
    // models.dev catalog fixture → 'catalog'
    expect(app(CostCalculator::class)->compute('anthropic', 'claude-fixture', null, tokens(['input' => 1]))['source'])
        ->toBe('catalog');
    // neither → null
    expect(app(CostCalculator::class)->compute('anthropic', 'mystery-model', null, tokens(['input' => 1]))['source'])
        ->toBeNull();
});
