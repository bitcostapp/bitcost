<?php

namespace App\Services;

use App\Models\ModelPricing;

class CostCalculator
{
    public function __construct(private readonly ModelCatalog $catalog) {}

    /**
     * Compute the cost of a usage turn from its token counts.
     *
     * Mirrors the CLI cost formula: Σ(tokens × pricePerMillion) / 1_000_000.
     * Pricing is resolved from the `model_pricings` table first, then the
     * committed models.dev catalog as a fallback. Unknown models yield an
     * unpriced result (cost stored as null, flagged) so the raw usage is still
     * captured and can be repriced later.
     *
     * @param  array{input?: int, output?: int, reasoning?: int, cache_read?: int, cache_write?: int}  $tokens
     * @return array{cost_total: float|null, cost_breakdown: array<string, float|bool>|null, pricing_id: int|null, is_priced: bool, currency: string, is_subscription: bool, source: 'pricing'|'catalog'|null}
     */
    public function compute(string $provider, string $model, ?string $variant, array $tokens): array
    {
        $pricing = ModelPricing::resolve($provider, $model, $variant);

        if ($pricing !== null) {
            return $this->priceFromRates(
                $tokens,
                input: (float) $pricing->input_price,
                output: (float) $pricing->output_price,
                reasoning: (float) ($pricing->reasoning_price ?? 0),
                cacheRead: (float) ($pricing->cache_read_price ?? 0),
                cacheWrite: (float) ($pricing->cache_write_price ?? 0),
                pricingId: $pricing->id,
                currency: $pricing->currency,
                isSubscription: $pricing->is_subscription,
                source: 'pricing',
            );
        }

        $row = $this->catalog->lookup($provider, $model, $variant);

        if ($row !== null) {
            return $this->priceFromRates(
                $tokens,
                input: (float) $row['input_price'],
                output: (float) $row['output_price'],
                reasoning: (float) ($row['reasoning_price'] ?? 0),
                cacheRead: (float) ($row['cache_read_price'] ?? 0),
                cacheWrite: (float) ($row['cache_write_price'] ?? 0),
                pricingId: null,
                currency: $row['currency'],
                isSubscription: false,
                source: 'catalog',
            );
        }

        return [
            'cost_total' => null,
            'cost_breakdown' => null,
            'pricing_id' => null,
            'is_priced' => false,
            'currency' => 'USD',
            'is_subscription' => false,
            'source' => null,
        ];
    }

    /**
     * Price a usage turn from explicit per-million-token rates.
     *
     * @param  array{input?: int, output?: int, reasoning?: int, cache_read?: int, cache_write?: int}  $tokens
     * @param  'pricing'|'catalog'  $source
     * @return array{cost_total: float, cost_breakdown: array<string, float|bool>, pricing_id: int|null, is_priced: true, currency: string, is_subscription: bool, source: 'pricing'|'catalog'}
     */
    private function priceFromRates(
        array $tokens,
        float $input,
        float $output,
        float $reasoning,
        float $cacheRead,
        float $cacheWrite,
        ?int $pricingId,
        string $currency,
        bool $isSubscription,
        string $source,
    ): array {
        $buckets = [
            'input' => [(int) ($tokens['input'] ?? 0), $input],
            'output' => [(int) ($tokens['output'] ?? 0), $output],
            'reasoning' => [(int) ($tokens['reasoning'] ?? 0), $reasoning],
            'cache_read' => [(int) ($tokens['cache_read'] ?? 0), $cacheRead],
            'cache_write' => [(int) ($tokens['cache_write'] ?? 0), $cacheWrite],
        ];

        $breakdown = [];
        $total = 0.0;
        foreach ($buckets as $name => [$count, $price]) {
            $cost = $count * $price / 1_000_000;
            $breakdown[$name] = $cost;
            $total += $cost;
        }

        if ($isSubscription) {
            // Per-token cost is notional for flat-fee subscriptions; keep it but flag it.
            $breakdown['notional'] = true;
        }

        return [
            'cost_total' => $total,
            'cost_breakdown' => $breakdown,
            'pricing_id' => $pricingId,
            'is_priced' => true,
            'currency' => $currency,
            'is_subscription' => $isSubscription,
            'source' => $source,
        ];
    }
}
