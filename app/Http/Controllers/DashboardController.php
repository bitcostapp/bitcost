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
                ->with('user:id,name')
                ->latest()
                ->get()
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status->value,
                    'owner' => ['name' => $task->user->name],
                ])
            : collect();

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'departmentTasks' => $departmentTasks,
            'departmentName' => $isDepartment ? $department->name : null,
        ]);
    }
}
