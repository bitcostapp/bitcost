<?php

namespace App\Models;

use Database\Factories\TaskPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $task_id
 * @property int|null $created_by
 * @property string|null $title
 * @property string $body
 * @property string $source
 * @property int $version
 * @property Carbon|null $created_at
 * @property-read Task $task
 */
#[Fillable(['task_id', 'created_by', 'title', 'body', 'source', 'version'])]
class TaskPlan extends Model
{
    /** @use HasFactory<TaskPlanFactory> */
    use HasFactory;

    /**
     * The task this plan belongs to.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }
}
