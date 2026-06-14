<?php

namespace App\Models;

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int $user_id
 * @property string $name
 * @property string|null $content
 * @property TaskStatus $status
 * @property string|null $external_url
 * @property string|null $external_provider
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team|null $team
 * @property-read User|null $user
 * @property-read TaskProvider $provider
 * @property-read Usage|null $latestUsage
 * @property-read TaskPlan|null $latestPlan
 * @property-read int|null $usages_count
 * @property-read int|null $usages_sum_tokens_input
 * @property-read int|null $usages_sum_tokens_output
 * @property-read string|null $usages_sum_cost_total
 */
#[Fillable(['team_id', 'user_id', 'name', 'content', 'status', 'external_url', 'external_provider', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The Department this task belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user who owns this task.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The usage turns reported against this task.
     *
     * @return HasMany<Usage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    /**
     * The plans/design docs attached to this task.
     *
     * @return HasMany<TaskPlan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(TaskPlan::class);
    }

    /**
     * The most recently reported usage for this task (used for currency).
     *
     * @return HasOne<Usage, $this>
     */
    public function latestUsage(): HasOne
    {
        return $this->hasOne(Usage::class)->latestOfMany('id');
    }

    /**
     * The latest plan/design doc attached to this task (highest version).
     *
     * @return HasOne<TaskPlan, $this>
     */
    public function latestPlan(): HasOne
    {
        return $this->hasOne(TaskPlan::class)->ofMany(['version' => 'max', 'id' => 'max']);
    }

    /**
     * Scope to open (non-completed) tasks.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', TaskStatus::Open);
    }

    /**
     * Scope to tasks owned by the given user.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * Scope to tasks in the given Department. A null or personal team narrows
     * to department-less tasks (a user with no Department is a valid state).
     *
     * @param  Builder<Task>  $query
     */
    public function scopeForDepartment(Builder $query, ?Team $team): void
    {
        $departmentId = $team && ! $team->is_personal ? $team->id : null;

        $query->where('team_id', $departmentId);
    }

    /**
     * The Work Provider this Task is linked to, or Internal when there is no
     * external link. Derived from `external_provider`.
     *
     * @return Attribute<TaskProvider, never>
     */
    protected function provider(): Attribute
    {
        return Attribute::get(
            fn (): TaskProvider => TaskProvider::fromExternalProvider($this->external_provider),
        );
    }

    /**
     * Determine if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::Completed;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
        ];
    }
}
