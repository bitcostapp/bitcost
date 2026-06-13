<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ModelPricingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string $model
 * @property string|null $variant
 * @property string $input_price
 * @property string $output_price
 * @property string|null $cache_read_price
 * @property string|null $cache_write_price
 * @property string|null $reasoning_price
 * @property string $currency
 * @property bool $is_subscription
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 */
#[Fillable([
    'provider', 'model', 'variant',
    'input_price', 'output_price', 'cache_read_price', 'cache_write_price', 'reasoning_price',
    'currency', 'is_subscription', 'effective_from', 'effective_until',
])]
class ModelPricing extends Model
{
    /** @use HasFactory<ModelPricingFactory> */
    use HasFactory;

    /**
     * Resolve the pricing for a model, preferring a variant-specific row, then
     * the base model, taking the most recent currently-effective entry.
     */
    public static function resolve(string $provider, string $model, ?string $variant = null): ?self
    {
        $candidates = static::query()
            ->where('provider', $provider)
            ->where('model', $model)
            ->effectiveAt(now())
            ->orderByDesc('effective_from')
            ->get();

        if ($variant !== null) {
            $match = $candidates->firstWhere('variant', $variant);
            if ($match !== null) {
                return $match;
            }
        }

        return $candidates->firstWhere('variant', null) ?? $candidates->first();
    }

    /**
     * Scope to rows effective at the given moment.
     *
     * @param  Builder<ModelPricing>  $query
     */
    public function scopeEffectiveAt(Builder $query, CarbonInterface $moment): void
    {
        $query
            ->where(fn (Builder $q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $moment))
            ->where(fn (Builder $q) => $q->whereNull('effective_until')->orWhere('effective_until', '>', $moment));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_price' => 'decimal:6',
            'output_price' => 'decimal:6',
            'cache_read_price' => 'decimal:6',
            'cache_write_price' => 'decimal:6',
            'reasoning_price' => 'decimal:6',
            'is_subscription' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }
}
