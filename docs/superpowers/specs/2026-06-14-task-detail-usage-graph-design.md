# Task detail page with per-session usage graph

## Context

Today a Task is only ever seen as a single read-only row in the Department dashboard
list. There is no way to click into a Task to see how its Cost and token Usage accrued.
Because a Task accumulates Usage across **one or more CLI Sessions** (see the `Session`
entry added to `CONTEXT.md`), the most useful drill-down is a breakdown *by Session*:
which run cost what. This spec adds a Department-scoped Task detail page reachable from
the dashboard, showing the Task's details, totals, a per-Session Cost/token bar chart,
a per-Session table, and the latest Plan rendered as markdown.

## Decisions (resolved during grilling)

- **Session model**: `Session` = one continuous CLI run, identified by `usages.session`.
  A Task has many Sessions; a Session has many per-turn Usage rows. Glossary updated.
- **Graph**: one bar per Session, **Cost** by default with a **tokens** toggle. Recharts.
- **Charting lib**: **Recharts** (approved dependency).
- **Markdown**: **`react-markdown` + `remark-gfm`**, styled with **`@tailwindcss/typography`**
  (approved). `react-markdown` ignores raw HTML → safe for CLI-supplied Plan bodies.
- **Authorization**: any member of the Task's **Department** can view; route is
  team-scoped and the Task only resolves if its `team_id` matches the current Department.
- **Reachability**: only **open** tasks are linked (from the dashboard). Completed tasks
  have no web entry point yet (deferred) but the page renders one if authorized.

## Backend

### Route
Add inside the existing team-scoped group in `routes/web.php` (alongside `dashboard`):

```php
Route::get('tasks/{task}', ShowTaskController::class)->name('tasks.show');
```

Path: `/{current_team}/tasks/{task}`, under `EnsureTeamMembership`.

### Controller — `app/Http/Controllers/ShowTaskController.php` (invokable)
Mirrors `DashboardController` style.

1. Resolve current Department: `$request->user()->currentTeam`; treat non-Department as
   "no Department" exactly like the dashboard does.
2. **Scope guard**: `abort_unless($task->team_id === $department?->id && $isDepartment, 404)`
   — prevents viewing another Department's Task by id.
3. Load `user:id,name`, `latestPlan`, and the Task's `usages` (ordered by `id`).
4. **Aggregate per Session in PHP** (small N for the POC; full control over "latest"):
   group `usages` by `session` (null → `'—'` "unattributed" bucket). For each Session emit:
   `session`, `turns` (count), `costTotal` (sum), `tokensInput` (sum), `tokensOutput`
   (sum), `tokensTotal`, `provider`/`model` (from the **latest** turn in that Session),
   `firstAt`/`lastAt` (min/max `created_at`). Order Sessions by `firstAt` ascending so
   the X-axis reads chronologically.
5. **Totals**: sum across all usages; `currency` from `latestUsage` (same convention as
   dashboard — single-currency assumption noted).
6. Render `Inertia::render('tasks/show', [...])` with `task` (id, name, status, owner,
   department, createdAt, externalUrl, externalProvider), `sessions`, `totals`,
   `latestPlan` (title, body), and `currency`.

> Note: API `TaskController` lives under the `Api` namespace, so the web
> `ShowTaskController` name does not clash. Verify during implementation.

## Frontend

### Dependencies
`npm i recharts react-markdown remark-gfm` and `npm i -D @tailwindcss/typography`.
Register typography for Tailwind v4 by adding to `resources/css/app.css`:
`@plugin "@tailwindcss/typography";`

### Types — `resources/js/types/teams.ts`
Add `TaskSession` and `TaskDetail` (or a `tasks.ts` module):

```ts
export type TaskSession = {
    session: string;            // '—' when unattributed
    turns: number;
    costTotal: number;
    tokensInput: number;
    tokensOutput: number;
    tokensTotal: number;
    provider: string;
    model: string;
    firstAt: string;            // ISO
    lastAt: string;
};
```

### Page — `resources/js/pages/tasks/show.tsx`
Breadcrumbs: Dashboard → task name (same `.layout` pattern as `dashboard.tsx`).
Sections:
1. **Header** — name, status `Badge`, owner, Department, created date, external Work
   Provider link (`external_url`) when set.
2. **Totals strip** — total Cost, total tokens, total turns, session count (reuse
   `formatCost`/`formatTokens`, lifted from `dashboard.tsx` into a shared module so both
   pages import them rather than duplicating).
3. **Per-session graph** — Recharts `<BarChart>`: X = session (shortened id), Y = Cost,
   with a toggle (Cost / tokens) flipping the `dataKey`. Tooltip shows turns + both
   metrics. Empty state when no usages: "No usage reported yet."
4. **Per-session table** — one row per Session: session (short), turns, cost,
   tokens **input / output / total**, model, time range.
5. **Latest Plan** — collapsible card; body rendered via `react-markdown` + `remark-gfm`
   inside a `prose` (typography) wrapper. Hidden when there is no Plan.

### Navigation
In `dashboard.tsx`, wrap each task row's name in an Inertia `<Link href={tasksShow([team, task.id])}>`
(Wayfinder-generated route fn). Only the listed open tasks become links.

## Shared helpers
Extract `formatCost` and `formatTokens` from `dashboard.tsx` into e.g.
`resources/js/lib/format.ts`; import in both pages. Avoids duplicating the Intl logic.

## Testing
`tests/Feature/TaskDetailTest.php` (Pest), using factories:
- Department member views their Department's Task → 200, Inertia page `tasks/show` with
  expected props.
- **Per-session aggregation**: seed usages across 2 sessions (+ some null-session rows);
  assert the `sessions` array buckets, sums (cost/tokens/turns), and chronological order.
- **Scope guard**: member of another Department requesting the Task id → 404.
- Task with **no usages** → renders with empty `sessions` and zero totals.
- **Completed** Task still renders for an authorized member.
- `latestPlan` body is passed through.

Run: `php artisan test --compact --filter=TaskDetail`. Also re-run `--filter=Dashboard`
after adding the row links. Frontend: `npx tsc --noEmit` + `npx eslint --fix` on changed
files; `npm run build` to confirm the bundle.

## Verification (end-to-end)
1. `npm run build` (or `npm run dev`).
2. Visit `/{team}/dashboard`, click an open task → lands on `/{team}/tasks/{id}`.
3. Confirm header, totals, the Cost bar chart, the Cost/tokens toggle, the session table,
   and the markdown-rendered Plan all render; tooltips work.

## Out of scope / deferred
- A "completed tasks" listing / web entry point.
- Token reasoning/cache split in the table (combined input/output/total only).
- Time-series and stacked-over-time chart shapes (chose per-session bars).
