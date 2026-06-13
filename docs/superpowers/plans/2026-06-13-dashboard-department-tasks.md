# Department Open Tasks on the User Dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show the signed-in user's department (current non-personal `Team`) open tasks on the Inertia dashboard, listing each task's name, owner, and status.

**Architecture:** `DashboardController` resolves the user's `currentTeam`, queries open tasks for that department via existing `Task::open()->forDepartment()` scopes, and passes them as Inertia props (mirroring the existing `pendingInvitations` pattern). The React `dashboard` page renders them in a `Card` replacing the large lower placeholder panel, with empty states for "no department" and "no open tasks".

**Tech Stack:** Laravel 13 + Inertia, React + TypeScript, Pest feature tests, Inertia `AssertableInertia`, shadcn-style UI primitives (`Card`, `Badge`).

**Working directory:** `/Users/threeel/code/bitcost/bitcost` (the Laravel app). Branch `feat/dashboard-department-tasks` already exists with the spec committed.

**Spec:** `docs/superpowers/specs/2026-06-13-dashboard-department-tasks-design.md`

---

## File Structure

- **Modify** `app/Http/Controllers/DashboardController.php` — add `departmentTasks` + `departmentName` Inertia props.
- **Modify** `tests/Feature/DashboardTest.php` — add feature tests for the new props.
- **Modify** `resources/js/types/teams.ts` — add the `DepartmentTask` type.
- **Modify** `resources/js/pages/dashboard.tsx` — consume the new props; render the task list card.

No new files, routes, or endpoints.

---

## Task 1: Backend — department task props on the dashboard

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Test: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add the `Task` import to the test file**

At the top of `tests/Feature/DashboardTest.php`, add `use App\Models\Task;` alongside the existing `use` statements (it currently imports `TeamRole`, `Team`, `TeamInvitation`, `User`, and `AssertableInertia as Assert`). Final import block:

```php
use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
```

- [ ] **Step 2: Write the failing tests**

Append these two tests to the end of `tests/Feature/DashboardTest.php`:

```php
test('dashboard includes open tasks for the current department', function () {
    $user = User::factory()->create();
    $department = Team::factory()->create(['name' => 'Engineering']); // is_personal = false
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    $openTask = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'name' => 'Fix login',
    ]);

    // Excluded: completed task in the same department.
    Task::factory()->completed()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    // Excluded: open task in a different department.
    $otherDepartment = Team::factory()->create();
    Task::factory()->create(['team_id' => $otherDepartment->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('departmentName', 'Engineering')
        ->has('departmentTasks', 1)
        ->where('departmentTasks.0.id', $openTask->id)
        ->where('departmentTasks.0.name', 'Fix login')
        ->where('departmentTasks.0.status', 'open')
        ->where('departmentTasks.0.owner.name', $user->name),
    );
});

test('dashboard shows no department tasks for a user without a department', function () {
    $user = User::factory()->create(); // currentTeam is a personal team

    // An unrelated open task in some other department must not leak in.
    Task::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('departmentName', null)
        ->has('departmentTasks', 0),
    );
});
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `./vendor/bin/pest --filter="department"`
Expected: FAIL — `departmentName` / `departmentTasks` props are missing (assertion failures on the new keys).

- [ ] **Step 4: Implement the controller change**

Replace the entire contents of `app/Http/Controllers/DashboardController.php` with:

```php
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
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./vendor/bin/pest --filter="department"`
Expected: PASS (both new tests green).

- [ ] **Step 6: Run the full dashboard test file to confirm no regressions**

Run: `./vendor/bin/pest tests/Feature/DashboardTest.php`
Expected: PASS (existing invitation tests still green).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/DashboardController.php tests/Feature/DashboardTest.php
git commit -m "feat: expose department open tasks on the dashboard"
```

---

## Task 2: Frontend — render the department task list

**Files:**
- Modify: `resources/js/types/teams.ts`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Add the `DepartmentTask` type**

Append to `resources/js/types/teams.ts` (this file is re-exported from `@/types`, which is where `dashboard.tsx` imports `DashboardInvitation`):

```ts
export type DepartmentTask = {
    id: number;
    name: string;
    status: string; // 'open'
    owner: { name: string };
};
```

- [ ] **Step 2: Replace the dashboard page with the task list**

Replace the entire contents of `resources/js/pages/dashboard.tsx` with:

```tsx
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import type { DashboardInvitation, DepartmentTask } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    departmentTasks?: DepartmentTask[];
    departmentName?: string | null;
};

export default function Dashboard({
    pendingInvitations = [],
    departmentTasks = [],
    departmentName = null,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <Card className="min-h-[100vh] flex-1 md:min-h-min">
                    <CardHeader>
                        <CardTitle>
                            {departmentName
                                ? `${departmentName} — open tasks`
                                : 'Department open tasks'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {!departmentName ? (
                            <p className="text-sm text-muted-foreground">
                                You are not part of a department yet.
                            </p>
                        ) : departmentTasks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No open tasks 🎉
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {departmentTasks.map((task) => (
                                    <li
                                        key={task.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <span className="truncate font-medium">
                                            {task.name}
                                        </span>
                                        <span className="flex shrink-0 items-center gap-3">
                                            <span className="text-sm text-muted-foreground">
                                                {task.owner.name}
                                            </span>
                                            <Badge variant="secondary">
                                                {task.status}
                                            </Badge>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
```

- [ ] **Step 3: Run the TypeScript type check**

Run: `npm run types:check`
Expected: PASS (no type errors; `DepartmentTask` resolves via `@/types`).

- [ ] **Step 4: Run the linter**

Run: `npm run lint:check`
Expected: PASS (no eslint errors in `dashboard.tsx` / `teams.ts`).

- [ ] **Step 5: Commit**

```bash
git add resources/js/types/teams.ts resources/js/pages/dashboard.tsx
git commit -m "feat: render department open tasks on the dashboard"
```

---

## Task 3: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Run the project test suite (lint + types + Pest)**

Run: `composer test`
Expected: PASS — runs `lint:check`, `types:check`, and `php artisan test` (the new dashboard tests included).

- [ ] **Step 2: Manual smoke check (optional but recommended)**

Run the app (`npm run dev` + serve), log in as a user whose current team is a non-personal department with a mix of open and completed tasks, and confirm the dashboard lower panel lists only the open tasks with owner name + an "open" badge. Switch to a user with only a personal team and confirm the "You are not part of a department yet." empty state.

---

## Notes / boundaries (from the spec)

- No new route or REST endpoint — Inertia props only.
- No async refresh/polling, no cost/usage columns, no pagination, no click-through to `external_url`.
- "Department" = the user's `currentTeam` when non-personal; a personal/absent current team is a valid "no department" state and must not fall back to department-less (`team_id IS NULL`) tasks.

---

## Task 4 (v2): Backend — usage & plan aggregates

**Files:**
- Modify: `app/Models/Task.php` (add `latestUsage` + `latestPlan` relations)
- Modify: `app/Http/Controllers/DashboardController.php` (aggregate the query + map)
- Test: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add `Usage` + `TaskPlan` imports to the test file**

Ensure `tests/Feature/DashboardTest.php` imports include:

```php
use App\Models\Task;
use App\Models\TaskPlan;
use App\Models\Usage;
```

- [ ] **Step 2: Extend the existing "current department" test to assert empty aggregates**

In the test `dashboard includes open tasks for the current department`, add these
assertions inside the existing `assertInertia` chain for `departmentTasks.0`
(the open task has no usages/plans, so aggregates default to zero/null):

```php
        ->where('departmentTasks.0.usageCount', 0)
        ->where('departmentTasks.0.tokensInput', 0)
        ->where('departmentTasks.0.tokensOutput', 0)
        ->where('departmentTasks.0.costTotal', 0)
        ->where('departmentTasks.0.currency', null)
        ->where('departmentTasks.0.planTitle', null)
```

- [ ] **Step 3: Add a new aggregate test**

Append to `tests/Feature/DashboardTest.php`:

```php
test('dashboard department tasks include usage and plan aggregates', function () {
    $user = User::factory()->create();
    $department = Team::factory()->create(['name' => 'Engineering']);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'name' => 'Fix login',
    ]);

    Usage::factory()->for($task)->create([
        'tokens_input' => 1000,
        'tokens_output' => 500,
        'cost_total' => '4.00',
        'currency' => 'USD',
    ]);
    Usage::factory()->for($task)->create([
        'tokens_input' => 2000,
        'tokens_output' => 1000,
        'cost_total' => '8.40',
        'currency' => 'USD',
    ]);
    TaskPlan::factory()->for($task)->create(['title' => 'Auth redesign', 'version' => 1]);
    TaskPlan::factory()->for($task)->create(['title' => 'Auth redesign v2', 'version' => 2]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('departmentTasks', 1)
        ->where('departmentTasks.0.usageCount', 2)
        ->where('departmentTasks.0.tokensInput', 3000)
        ->where('departmentTasks.0.tokensOutput', 1500)
        ->where('departmentTasks.0.costTotal', 12.4)
        ->where('departmentTasks.0.currency', 'USD')
        ->where('departmentTasks.0.planTitle', 'Auth redesign v2'),
    );
});
```

- [ ] **Step 4: Run the tests, confirm they FAIL**

Run: `./vendor/bin/pest --filter="department|aggregates"`
Expected: FAIL (aggregate fields missing).

- [ ] **Step 5: Add the relations to `app/Models/Task.php`**

Add `use Illuminate\Database\Eloquent\Relations\HasOne;` to the imports, then add
these two methods to the `Task` class (next to the existing `usages()` / `plans()`):

```php
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
        return $this->hasOne(TaskPlan::class)->latestOfMany('version');
    }
```

- [ ] **Step 6: Update the `departmentTasks` query + map in `DashboardController`**

Replace the `$departmentTasks = $isDepartment ? ... : collect();` assignment with:

```php
        $departmentTasks = $isDepartment
            ? Task::query()
                ->open()
                ->forDepartment($department)
                ->with([
                    'user:id,name',
                    'latestUsage:id,task_id,currency',
                    'latestPlan:id,task_id,title,version',
                ])
                ->withCount('usages')
                ->withSum('usages', 'cost_total')
                ->withSum('usages', 'tokens_input')
                ->withSum('usages', 'tokens_output')
                ->latest()
                ->get()
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
```

- [ ] **Step 7: Run the tests, confirm they PASS**

Run: `./vendor/bin/pest --filter="department|aggregates"`
Expected: PASS.

- [ ] **Step 8: Run the full suite for regressions**

Run: `php artisan test`
Expected: PASS (all tests).

- [ ] **Step 9: Commit**

```bash
git add app/Models/Task.php app/Http/Controllers/DashboardController.php tests/Feature/DashboardTest.php
git commit -m "feat: add usage and plan aggregates to department dashboard tasks"
```

---

## Task 5 (v2): Frontend — render aggregates

**Files:**
- Modify: `resources/js/types/teams.ts`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Extend the `DepartmentTask` type in `resources/js/types/teams.ts`**

Replace the existing `DepartmentTask` type with:

```ts
export type DepartmentTask = {
    id: number;
    name: string;
    status: string; // 'open'
    owner: { name: string };
    usageCount: number;
    tokensInput: number;
    tokensOutput: number;
    costTotal: number;
    currency: string | null;
    planTitle: string | null;
};
```

- [ ] **Step 2: Add formatting helpers + render aggregates in `resources/js/pages/dashboard.tsx`**

Add these module-scope helpers (above the `Dashboard` component):

```tsx
function formatCost(amount: number, currency: string | null): string {
    if (!currency) {
        return amount.toFixed(2);
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}

function formatTokens(total: number): string {
    return new Intl.NumberFormat(undefined, {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(total);
}
```

Replace the `<li>` task row inside the list with:

```tsx
                                {departmentTasks.map((task) => (
                                    <li
                                        key={task.id}
                                        className="flex items-start justify-between gap-4 py-3"
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium">
                                                {task.name}
                                            </span>
                                            {task.planTitle ? (
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    plan: {task.planTitle}
                                                </span>
                                            ) : null}
                                        </span>
                                        <span className="flex shrink-0 items-center gap-3 text-sm text-muted-foreground">
                                            <span>{task.owner.name}</span>
                                            <Badge variant="secondary">
                                                {task.status}
                                            </Badge>
                                            <span>
                                                {formatCost(
                                                    task.costTotal,
                                                    task.currency,
                                                )}
                                            </span>
                                            <span>
                                                {formatTokens(
                                                    task.tokensInput +
                                                        task.tokensOutput,
                                                )}{' '}
                                                tok
                                            </span>
                                            <span>
                                                {task.usageCount}{' '}
                                                {task.usageCount === 1
                                                    ? 'turn'
                                                    : 'turns'}
                                            </span>
                                        </span>
                                    </li>
                                ))}
```

- [ ] **Step 3: Run `npm run types:check`** — expect PASS.
- [ ] **Step 4: Run `npm run lint:check`** — expect PASS (run `npm run format` first if needed; do not change logic).
- [ ] **Step 5: Commit**

```bash
git add resources/js/types/teams.ts resources/js/pages/dashboard.tsx
git commit -m "feat: render usage and plan aggregates on department dashboard"
```
