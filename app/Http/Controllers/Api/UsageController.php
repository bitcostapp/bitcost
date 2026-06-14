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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsageController extends Controller
{
    /**
     * Record a usage turn against a task. Idempotent on (task, idempotency_key);
     * rejected once the task is completed.
     */
    public function store(StoreUsageRequest $request, Task $task, CostCalculator $calculator): JsonResponse
    {
        Gate::authorize('report', $task);
        $traceId = $this->traceId($request, $task);

        if ($task->isCompleted()) {
            abort(409, 'Task is completed and locked.');
        }

        $provider = (string) $request->input('provider');
        $model = (string) $request->input('model');
        $variant = $request->input('variant');
        $tokens = $request->tokenCounts();
        $cost = $calculator->compute($provider, $model, $variant, $tokens);

        // The CLI sends its own catalog-computed turn cost. It is always kept for
        // audit; it only becomes the authoritative cost_total when the server
        // could not price the model itself.
        $clientCost = $request->has('cost') ? (float) $request->input('cost') : null;
        if ($cost['is_priced']) {
            $costTotal = $cost['cost_total'];
            $costSource = $cost['source'];
            $currency = $cost['currency'];
        } elseif ($clientCost !== null) {
            $costTotal = $clientCost;
            $costSource = 'client';
            $currency = 'USD';
        } else {
            $costTotal = null;
            $costSource = null;
            $currency = $cost['currency'];
        }

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
                'cost_total' => $costTotal,
                'cost_breakdown' => $cost['cost_breakdown'],
                'client_cost_total' => $clientCost,
                'currency' => $currency,
                'is_subscription' => $cost['is_subscription'],
                'is_priced' => $cost['is_priced'],
                'cost_source' => $costSource,
                'pricing_id' => $cost['pricing_id'],
                'reported_at' => $request->input('reported_at'),
            ],
        );

        Log::info('bitcost.api.usage.store', [
            'trace_id' => $traceId,
            'task_id' => $task->id,
            'usage_id' => $usage->id,
            'session' => $usage->session,
            'idempotency_key' => $usage->idempotency_key,
            'request_id' => $usage->request_id,
            'was_created' => $usage->wasRecentlyCreated,
        ]);

        return UsageResource::make($usage)
            ->response()
            ->setStatusCode($usage->wasRecentlyCreated ? 201 : 200)
            ->header('X-Bitcost-Trace-ID', $traceId);
    }

    private function traceId(StoreUsageRequest $request, Task $task): string
    {
        $traceId = $request->input('request_id');
        if (is_string($traceId) && $traceId !== '') {
            return $traceId;
        }

        return 'usage:'.$task->id.':'.Str::uuid();
    }
}
