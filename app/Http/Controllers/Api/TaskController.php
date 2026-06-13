<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tasks\CreateTask;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskPlanRequest;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Resources\TaskPlanResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * List the authenticated user's open tasks in their current Department.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $tasks = Task::query()
            ->open()
            ->forUser($user)
            ->forDepartment($user->currentTeam)
            ->withSum('usages', 'cost_total')
            ->withCount('usages')
            ->latest()
            ->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Create a new task for the authenticated user.
     */
    public function store(StoreTaskRequest $request, CreateTask $createTask): JsonResponse
    {
        $task = $createTask->handle($request->user(), $request->validated());

        return TaskResource::make($task)->response()->setStatusCode(201);
    }

    /**
     * Mark a task complete, locking it from further usage. Idempotent.
     */
    public function complete(Request $request, Task $task): TaskResource
    {
        Gate::authorize('complete', $task);

        if (! $task->isCompleted()) {
            $task->update([
                'status' => TaskStatus::Completed,
                'completed_at' => now(),
            ]);
        }

        return TaskResource::make($task);
    }

    /**
     * List the plans attached to a task, newest version first.
     */
    public function plans(Request $request, Task $task): AnonymousResourceCollection
    {
        Gate::authorize('view', $task);

        return TaskPlanResource::collection($task->plans()->orderByDesc('version')->get());
    }

    /**
     * Attach a new plan version to a task (kept for later review).
     */
    public function storePlan(StoreTaskPlanRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $plan = $task->plans()->create([
            'created_by' => $request->user()->id,
            'title' => $request->input('title'),
            'body' => (string) $request->input('body'),
            'source' => 'cli',
            'version' => ($task->plans()->max('version') ?? 0) + 1,
        ]);

        return TaskPlanResource::make($plan)->response()->setStatusCode(201);
    }
}
