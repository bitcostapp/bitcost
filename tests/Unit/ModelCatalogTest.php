<?php

use App\Services\ModelCatalog;

function fixtureCatalog(): ModelCatalog
{
    return new ModelCatalog(dirname(__DIR__).'/Fixtures/model-pricing.json');
}

test('it looks up a model by provider and model', function () {
    $row = fixtureCatalog()->lookup('anthropic', 'claude-fixture');

    expect($row)->not->toBeNull()
        ->and($row['input_price'])->toBe(3.0)
        ->and($row['output_price'])->toBe(15.0)
        ->and($row['currency'])->toBe('USD');
});

test('it returns null for a model not in the catalog', function () {
    expect(fixtureCatalog()->lookup('anthropic', 'mystery-model'))->toBeNull();
});

test('it falls back to the base model when a variant is not found', function () {
    $row = fixtureCatalog()->lookup('anthropic', 'claude-fixture', 'thinking');

    expect($row)->not->toBeNull()
        ->and($row['model'])->toBe('claude-fixture');
});

test('all returns every catalog row', function () {
    expect(fixtureCatalog()->all())->toHaveCount(2);
});
