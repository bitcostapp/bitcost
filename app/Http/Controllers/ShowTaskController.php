<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Usage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShowTaskController extends Controller
{
    /**
     * Display a single task with its per-session usage breakdown.
     */
    public function __invoke(Request $request, string $current_team, Task $task): Response
    {
        $department = $request->user()->currentTeam;
        $isDepartment = $department && ! $department->is_personal;

        // A task only resolves within its own Department — you cannot view
        // another Department's task by guessing its id.
        abort_unless($isDepartment && $task->team_id === $department->id, 404);

        $task->load(['user:id,name', 'latestPlan']);

        /** @var Collection<int, Usage> $usages */
        $usages = $task->usages()->orderBy('id')->get();

        $sessions = $this->aggregateSessions($usages);

        return Inertia::render('tasks/show', [
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'content' => $task->content,
                'status' => $task->status->value,
                'owner' => ['name' => $task->user?->name],
                'departmentName' => $department->name,
                'createdAt' => $task->created_at?->toIso8601String(),
                'externalUrl' => $task->external_url,
                'externalProvider' => $task->external_provider,
            ],
            'sessions' => $sessions,
            'totals' => [
                'sessionCount' => count($sessions),
                'turns' => (int) $usages->count(),
                'tokensInput' => (int) $usages->sum('tokens_input'),
                'tokensOutput' => (int) $usages->sum('tokens_output'),
                'costTotal' => (float) $usages->sum(fn (Usage $usage) => (float) $usage->cost_total),
            ],
            'currency' => $usages->last()?->currency,
            'latestPlan' => $task->latestPlan
                ? ['title' => $task->latestPlan->title, 'body' => $task->latestPlan->body]
                : null,
        ]);
    }

    /**
     * Group a task's usage turns into sessions, ordered chronologically.
     *
     * @param  Collection<int, Usage>  $usages
     * @return array<int, array{
     *     session: string,
     *     turns: int,
     *     costTotal: float,
     *     tokensInput: int,
     *     tokensOutput: int,
     *     tokensTotal: int,
     *     provider: string,
     *     model: string,
     *     firstAt: string|null,
     *     lastAt: string|null,
     * }>
     */
    private function aggregateSessions(Collection $usages): array
    {
        return $usages
            ->groupBy(fn (Usage $usage) => $usage->session ?? '—')
            ->map(function (Collection $turns, string $session) {
                // The latest turn (highest id) names the provider/model for the session.
                $latest = $turns->sortByDesc('id')->firstOrFail();
                $tokensInput = (int) $turns->sum('tokens_input');
                $tokensOutput = (int) $turns->sum('tokens_output');

                return [
                    'session' => $session,
                    'turns' => $turns->count(),
                    'costTotal' => (float) $turns->sum(fn (Usage $usage) => (float) $usage->cost_total),
                    'tokensInput' => $tokensInput,
                    'tokensOutput' => $tokensOutput,
                    'tokensTotal' => $tokensInput + $tokensOutput,
                    'provider' => $latest->provider,
                    'model' => $latest->model,
                    'firstAt' => $turns->sortBy('created_at')->firstOrFail()->created_at?->toIso8601String(),
                    'lastAt' => $turns->sortByDesc('created_at')->firstOrFail()->created_at?->toIso8601String(),
                ];
            })
            ->sortBy('firstAt')
            ->values()
            ->all();
    }
}
