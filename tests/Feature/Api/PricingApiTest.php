<?php

use App\Models\ModelPricing;
use App\Models\User;
use Laravel\Passport\Passport;

test('resolving pricing requires authentication', function () {
    $this->getJson('/api/pricing?provider=anthropic&model=claude-opus-4-8')->assertUnauthorized();
});

test('provider and model are required', function () {
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/pricing')->assertJsonValidationErrors(['provider', 'model']);
});

test('resolves rates for a known model', function () {
    Passport::actingAs(User::factory()->create());

    ModelPricing::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-opus-4-8',
        'variant' => null,
        'input_price' => 5.0,
        'output_price' => 25.0,
        'cache_read_price' => 0.5,
    ]);

    $this->getJson('/api/pricing?provider=anthropic&model=claude-opus-4-8')
        ->assertOk()
        ->assertJsonPath('data.provider', 'anthropic')
        ->assertJsonPath('data.model', 'claude-opus-4-8')
        ->assertJsonPath('data.input_price', 5)
        ->assertJsonPath('data.output_price', 25)
        ->assertJsonPath('data.cache_read_price', 0.5);
});

test('prefers a variant-specific row over the base model', function () {
    Passport::actingAs(User::factory()->create());

    ModelPricing::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-opus-4-8',
        'variant' => null,
        'input_price' => 5.0,
    ]);
    ModelPricing::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-opus-4-8',
        'variant' => 'thinking',
        'input_price' => 9.0,
    ]);

    $this->getJson('/api/pricing?provider=anthropic&model=claude-opus-4-8&variant=thinking')
        ->assertOk()
        ->assertJsonPath('data.variant', 'thinking')
        ->assertJsonPath('data.input_price', 9);
});

test('returns null data when no pricing exists for the model', function () {
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/pricing?provider=acme&model=ghost')
        ->assertOk()
        ->assertJsonPath('data', null);
});
