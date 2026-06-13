<?php

namespace App\Services;

use App\Models\ModelPricing;

class CostCalculator
{
    /**
     * Compute the cost of a usage turn from its token counts.
     *
     * Mirrors the CLI cost formula: Σ(tokens × pricePerMillion) / 1_000_000.
     * Unknown models yield an unpriced result (cost stored as null, flagged)
     * so the raw usage is still captured and can be repriced later.
     *
     * @param  array{input?: int, output?: int, reasoning?: int, cache_read?: int, cache_write?: int}  $tokens
     * @return array{cost_total: float|null, cost_breakdown: array<string, float>|null, pricing_id: int|null, is_priced: bool, currency: string, is_subscription: bool}
     */
    public function compute(string $provider, string $model, ?string $variant, array $tokens): array
    {
        $pricing = ModelPricing::resolve($provider, $model, $variant);

        if ($pricing === null) {
            return [
                'cost_total' => null,
                'cost_breakdown' => null,
                'pricing_id' => null,
                'is_priced' => false,
                'currency' => 'USD',
                'is_subscription' => false,
            ];
        }

        $buckets = [
            'input' => [(int) ($tokens['input'] ?? 0), (float) $pricing->input_price],
            'output' => [(int) ($tokens['output'] ?? 0), (float) $pricing->output_price],
            'reasoning' => [(int) ($tokens['reasoning'] ?? 0), (float) ($pricing->reasoning_price ?? 0)],
            'cache_read' => [(int) ($tokens['cache_read'] ?? 0), (float) ($pricing->cache_read_price ?? 0)],
            'cache_write' => [(int) ($tokens['cache_write'] ?? 0), (float) ($pricing->cache_write_price ?? 0)],
        ];

        $breakdown = [];
        $total = 0.0;
        foreach ($buckets as $name => [$count, $price]) {
            $cost = $count * $price / 1_000_000;
            $breakdown[$name] = $cost;
            $total += $cost;
        }

        if ($pricing->is_subscription) {
            // Per-token cost is notional for flat-fee subscriptions; keep it but flag it.
            $breakdown['notional'] = true;
        }

        return [
            'cost_total' => $total,
            'cost_breakdown' => $breakdown,
            'pricing_id' => $pricing->id,
            'is_priced' => true,
            'currency' => $pricing->currency,
            'is_subscription' => $pricing->is_subscription,
        ];
    }
}
