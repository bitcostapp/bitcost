<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $user = $request->user();
        $department = $user->currentTeam;
        $isDepartment = $department && ! $department->is_personal;

        $departmentTasks = $isDepartment
            ? $this->mapTasks($this->openDepartmentTasks($department))
            : collect();

        $myTasks = $isDepartment
            ? $this->mapTasks($this->openDepartmentTasks($department)->forUser($user))
            : collect();

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'departmentTasks' => $departmentTasks,
            'myTasks' => $myTasks,
            'departmentName' => $isDepartment ? $department->name : null,
        ]);
    }

    /**
     * Base query for open tasks in a Department, eager-loading the data the
     * dashboard task lists render.
     *
     * @return Builder<Task>
     */
    private function openDepartmentTasks(Team $department): Builder
    {
        return Task::query()
            ->open()
            ->forDepartment($department)
            ->with([
                'user:id,name',
                'latestUsage',
                'latestPlan',
            ])
            ->withCount('usages')
            ->withSum('usages', 'cost_total')
            ->withSum('usages', 'tokens_input')
            ->withSum('usages', 'tokens_output')
            ->latest();
    }

    /**
     * Shape tasks for the dashboard. `costTotal` sums cost_total across all
     * usages; `currency` reflects the latest usage only — correct for the
     * normal single-currency case.
     *
     * @param  Builder<Task>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function mapTasks(Builder $query): Collection
    {
        return $query->get()->map(fn (Task $task) => [
            'id' => $task->id,
            'name' => $task->name,
            'status' => $task->status->value,
            'owner' => ['name' => $task->user?->name ?? 'Unassigned'],
            'usageCount' => (int) $task->usages_count,
            'tokensInput' => (int) $task->usages_sum_tokens_input,
            'tokensOutput' => (int) $task->usages_sum_tokens_output,
            'costTotal' => (float) $task->usages_sum_cost_total,
            'currency' => $task->latestUsage?->currency,
            'planTitle' => $task->latestPlan?->title,
        ]);
    }
}
