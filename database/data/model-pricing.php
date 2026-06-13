<?php

/**
 * Curated per-model pricing snapshot (USD per 1,000,000 tokens), mirroring
 * models.dev. The CLI fetches models.dev live; the server is the source of
 * truth for cost, so it seeds a static table. Extend via `pricing:sync` later.
 *
 * @return array<int, array<string, mixed>>
 */
return [
    [
        'provider' => 'anthropic',
        'model' => 'claude-opus-4-8',
        'input_price' => 5.0,
        'output_price' => 25.0,
        'cache_read_price' => 0.5,
        'cache_write_price' => 6.25,
        'reasoning_price' => 0.0,
    ],
    [
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-4-6',
        'input_price' => 3.0,
        'output_price' => 15.0,
        'cache_read_price' => 0.3,
        'cache_write_price' => 3.75,
        'reasoning_price' => 0.0,
    ],
    [
        'provider' => 'anthropic',
        'model' => 'claude-haiku-4-5',
        'input_price' => 1.0,
        'output_price' => 5.0,
        'cache_read_price' => 0.1,
        'cache_write_price' => 1.25,
        'reasoning_price' => 0.0,
    ],
    [
        'provider' => 'openai',
        'model' => 'gpt-5',
        'input_price' => 1.25,
        'output_price' => 10.0,
        'cache_read_price' => 0.125,
        'cache_write_price' => 0.0,
        'reasoning_price' => 0.0,
    ],
    [
        'provider' => 'openai',
        'model' => 'gpt-5.4',
        'input_price' => 1.25,
        'output_price' => 10.0,
        'cache_read_price' => 0.125,
        'cache_write_price' => 0.0,
        'reasoning_price' => 0.0,
    ],
];
