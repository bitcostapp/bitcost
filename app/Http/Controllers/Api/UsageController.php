<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUsageRequest;
use App\Http\Resources\UsageResource;
use App\Models\Task;
use App\Models\Usage;
use App\Services\CostCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UsageController extends Controller
{
    /**
     * Record a usage turn against a task. Idempotent on (task, idempotency_key);
     * rejected once the task is completed.
     */
    public function store(StoreUsageRequest $request, Task $task, CostCalculator $calculator): JsonResponse
    {
        Gate::authorize('report', $task);

        if ($task->isCompleted()) {
            abort(409, 'Task is completed and locked.');
        }

        $provider = (string) $request->input('provider');
        $model = (string) $request->input('model');
        $variant = $request->input('variant');
        $tokens = $request->tokenCounts();
        $cost = $calculator->compute($provider, $model, $variant, $tokens);

        $usage = Usage::firstOrCreate(
            [
                'task_id' => $task->id,
                'idempotency_key' => (string) $request->input('idempotency_key'),
            ],
            [
                'session' => $request->input('session'),
                'request_id' => $request->input('request_id'),
                'provider' => $provider,
                'model' => $model,
                'variant' => $variant,
                'tokens_input' => $tokens['input'],
                'tokens_output' => $tokens['output'],
                'tokens_reasoning' => $tokens['reasoning'],
                'tokens_cache_read' => $tokens['cache_read'],
                'tokens_cache_write' => $tokens['cache_write'],
                'cost_total' => $cost['cost_total'],
                'cost_breakdown' => $cost['cost_breakdown'],
                'currency' => $cost['currency'],
                'is_subscription' => $cost['is_subscription'],
                'is_priced' => $cost['is_priced'],
                'pricing_id' => $cost['pricing_id'],
                'reported_at' => $request->input('reported_at'),
            ],
        );

        return UsageResource::make($usage)
            ->response()
            ->setStatusCode($usage->wasRecentlyCreated ? 201 : 200);
    }
}
