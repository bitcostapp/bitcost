<?php

namespace App\Models;

use Database\Factories\UsageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $task_id
 * @property string $idempotency_key
 * @property string|null $session
 * @property string|null $request_id
 * @property string $provider
 * @property string $model
 * @property string|null $variant
 * @property int $tokens_input
 * @property int $tokens_output
 * @property int $tokens_reasoning
 * @property int $tokens_cache_read
 * @property int $tokens_cache_write
 * @property string|null $cost_total
 * @property array<string, mixed>|null $cost_breakdown
 * @property string|null $client_cost_total
 * @property string $currency
 * @property bool $is_subscription
 * @property bool $is_priced
 * @property string|null $cost_source
 * @property int|null $pricing_id
 * @property Carbon|null $reported_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Task $task
 * @property-read ModelPricing|null $pricing
 */
#[Fillable([
    'task_id', 'idempotency_key', 'session', 'request_id', 'provider', 'model', 'variant',
    'tokens_input', 'tokens_output', 'tokens_reasoning', 'tokens_cache_read', 'tokens_cache_write',
    'cost_total', 'cost_breakdown', 'client_cost_total', 'currency', 'is_subscription', 'is_priced',
    'cost_source', 'pricing_id', 'reported_at',
])]
class Usage extends Model
{
    /** @use HasFactory<UsageFactory> */
    use HasFactory;

    /**
     * The task this usage is attributed to.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * The pricing row applied when computing cost.
     *
     * @return BelongsTo<ModelPricing, $this>
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(ModelPricing::class, 'pricing_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'tokens_reasoning' => 'integer',
            'tokens_cache_read' => 'integer',
            'tokens_cache_write' => 'integer',
            'cost_total' => 'decimal:10',
            'cost_breakdown' => 'array',
            'client_cost_total' => 'decimal:10',
            'is_subscription' => 'boolean',
            'is_priced' => 'boolean',
            'reported_at' => 'datetime',
        ];
    }
}
