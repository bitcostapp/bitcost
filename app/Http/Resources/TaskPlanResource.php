<?php

namespace App\Http\Resources;

use App\Models\TaskPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskPlan
 */
class TaskPlanResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'source' => $this->source,
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
