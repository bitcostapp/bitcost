<?php

namespace App\Http\Resources;

use App\Models\Usage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usage
 */
class UsageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'idempotency_key' => $this->idempotency_key,
            'model' => $this->model,
            'provider' => $this->provider,
            'variant' => $this->variant,
            'cost_total' => $this->cost_total === null ? null : (float) $this->cost_total,
            'currency' => $this->currency,
            'is_priced' => $this->is_priced,
            'is_subscription' => $this->is_subscription,
        ];
    }
}
