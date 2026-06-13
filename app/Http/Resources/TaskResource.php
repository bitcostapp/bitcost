<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'external_url' => $this->external_url,
            'external_provider' => $this->external_provider,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // Cost rollup is the sum of usage costs, eager-loaded via withSum;
            // null when the aggregate was not loaded (SQL SUM skips unpriced nulls).
            'cost_total' => array_key_exists('usages_sum_cost_total', $attributes)
                ? (float) ($attributes['usages_sum_cost_total'] ?? 0)
                : null,
            'usage_count' => $this->whenCounted('usages'),
        ];
    }
}
