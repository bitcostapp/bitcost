<?php

namespace App\Http\Resources;

use App\Models\ModelPricing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModelPricing
 */
class PricingResource extends JsonResource
{
    /**
     * Transform the resource into an array. Prices are per 1,000,000 tokens.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'variant' => $this->variant,
            'input_price' => (float) $this->input_price,
            'output_price' => (float) $this->output_price,
            'cache_read_price' => $this->cache_read_price === null ? null : (float) $this->cache_read_price,
            'cache_write_price' => $this->cache_write_price === null ? null : (float) $this->cache_write_price,
            'reasoning_price' => $this->reasoning_price === null ? null : (float) $this->reasoning_price,
            'currency' => $this->currency,
            'is_subscription' => $this->is_subscription,
        ];
    }
}
