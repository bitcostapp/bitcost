# Department open tasks on the user dashboard

**Date:** 2026-06-13
**Repo:** `bitcost` (Laravel + React/Inertia web app)
**Status:** Approved design — pending implementation plan

## Goal

On the Inertia user dashboard, show the **open tasks belonging to the signed-in
user's department** (their current non-personal `Team`), across all members of
that department. Read-only overview.

## Context

- A "department" is modelled as a non-personal `Team`. A user's department is
  their `currentTeam` (`User.current_team_id` → `currentTeam`), when that team
  exists and `is_personal === false`.
- The model layer already supports this:
  - `Task::scopeOpen()` → `where('status', TaskStatus::Open)`
  - `Task::scopeForDepartment(?Team $team)` → tasks where `team_id` matches the
    department.
  - `Task` belongs to `user()` (owner) and `team()` (department).
- `TaskStatus` enum: `Open` (`'open'`) / `Completed` (`'completed'`).
- The dashboard is an Inertia page (`resources/js/pages/dashboard.tsx`),
  currently placeholder cards, fed by
  `app/Http/Controllers/DashboardController.php`, which already passes
  `currentTeam` (via layout) and `pendingInvitations`.

## Design

### 1. Backend — `DashboardController`

Resolve the department and query its open tasks, passed as a new Inertia prop
alongside the existing `pendingInvitations`:

```php
$user = $request->user();
$department = $user->currentTeam; // Team|null
$isDepartment = $department && ! $department->is_personal;

$departmentTasks = $isDepartment
    ? Task::query()
        ->open()
        ->forDepartment($department)
        ->with('user:id,name')
        ->latest()
        ->get()
        ->map(fn (Task $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'status' => $t->status->value, // 'open'
            'owner' => ['name' => $t->user->name],
        ])
    : collect();

return Inertia::render('dashboard', [
    'pendingInvitations' => $pendingInvitations,
    'departmentTasks' => $departmentTasks,
    'departmentName' => $isDepartment ? $department->name : null,
]);
```

- Reuses existing scopes `open()` + `forDepartment()`.
- `with('user:id,name')` avoids N+1 on the owner name.
- `latest()` orders newest-first.
- When the user has no department (null or personal team): empty collection +
  `departmentName: null`. We deliberately do **not** pass `null` to
  `forDepartment()` (that would match department-less `team_id IS NULL` tasks,
  which is not the intent here).

### 2. Frontend — `resources/js/pages/dashboard.tsx`

Replace the large lower placeholder panel with the task list. The three top
placeholder cards stay as-is (out of scope).

- Extend `Props` with `departmentTasks?: DepartmentTask[]` and
  `departmentName?: string | null`.
- Reuse existing UI primitives: `Card` for the panel, `Badge` for the status,
  `Avatar` (optional) for the owner.
- Row layout: task **name** on the left; **owner.name** + **status badge** on
  the right.
- Add a `DepartmentTask` type next to `DashboardInvitation` in
  `resources/js/types/teams.ts`:

  ```ts
  export type DepartmentTask = {
      id: number;
      name: string;
      status: string; // 'open'
      owner: { name: string };
  };
  ```

### 3. Empty / edge states

- **No department** (null or personal current team): show
  "You're not part of a department yet."
- **Department with zero open tasks:** show "No open tasks 🎉".
- Soft-deleted tasks excluded automatically (`Task` uses `SoftDeletes`).
- Completed tasks excluded by the `open()` scope.

### 4. Testing (Pest feature tests on the dashboard route)

- A user whose current (non-personal) team has both open and completed tasks,
  plus open tasks from another team, sees **only** their department's open tasks
  in the `departmentTasks` prop (assert via Inertia assertions).
- Open tasks from a different department are excluded.
- A user with only a personal team → `departmentTasks` empty and
  `departmentName` null.

## Scope boundaries (YAGNI)

- No new route/REST endpoint — Inertia props from `DashboardController` only.
- No async refresh/polling.
- No cost/usage columns.
- No pagination (department open-task counts are small; revisit if they grow).
- No click-through to `external_url`.
