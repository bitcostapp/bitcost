<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
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

        $department = $request->user()->currentTeam;
        $isDepartment = $department && ! $department->is_personal;

        $departmentTasks = $isDepartment
            ? Task::query()
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
                ->latest()
                ->get()
                // `costTotal` sums cost_total across all usages; `currency` reflects
                // the latest usage only — correct for the normal single-currency case.
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status->value,
                    'owner' => ['name' => $task->user->name],
                    'usageCount' => (int) $task->usages_count,
                    'tokensInput' => (int) $task->usages_sum_tokens_input,
                    'tokensOutput' => (int) $task->usages_sum_tokens_output,
                    'costTotal' => (float) $task->usages_sum_cost_total,
                    'currency' => $task->latestUsage?->currency,
                    'planTitle' => $task->latestPlan?->title,
                ])
            : collect();

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'departmentTasks' => $departmentTasks,
            'departmentName' => $isDepartment ? $department->name : null,
        ]);
    }
}
