# Prefill the prompt with a task's latest plan on first `/task` bind

**Date:** 2026-06-13
**Repo:** `bitcost-cli` (opencode-fork CLI). Backend (`bitcost` Laravel app) is unchanged.
**Related:** "session is the task" feature, token-pricing work.

## Goal

When a user binds a Bitcost task via `/task` and the session is empty, pull the
task's **latest plan body** into the prompt input as editable text, so the user
can add more information before sending their first message. Nothing is saved
back to Bitcost — the prefill is a one-way convenience seed.

## Decisions (resolved)

- **What "content" is:** the latest `task_plan` `body` (the only rich content a
  task carries; the `tasks` table itself has only `name`/`status`/`external_url`).
- **How it lands:** prefilled into the **editable** prompt textarea. The user
  edits/appends and sends it as their first message. No write-back to Bitcost.
- **When ("the first time"):** only when binding an **empty session** (no
  messages). If the session already has a conversation, bind silently as today.
- **Existing-session double-gate (confirmed):** prefill only when the session has
  no messages **and** the prompt input is currently empty (never clobber text the
  user already typed).

## Architecture

The logic lives in `DialogBitcostTask.bind()` (`packages/tui/src/component/
dialog-bitcost-task.tsx`) — the single chokepoint both bind paths already flow
through. The `/task` command's `run()` in `app.tsx` is left untouched.

Rejected alternative: a `TaskBound`-event projector that prefills reactively —
over-engineered for a one-shot seed and harder to gate to "this bind".

### Components

1. **`fetchLatestTaskPlan(taskID)`** — new function in
   `packages/tui/src/component/bitcost-api.ts`.
   - Calls existing `GET /api/tasks/{task}/plans` (returns plans newest-version
     first, see `TaskController@plans`).
   - Returns `data[0] ?? null`.
   - **Never throws**: returns `null` on no-plan, not-logged-in, auth expiry, or
     network/timeout failure. A missing or unreachable plan must not block the
     bind.
   - New exported type `BitcostTaskPlan`:
     `{ id; task_id; title?: string | null; body: string; source?: string;
     version?: number; created_at?: string | null }` (matches `TaskPlanResource`).

2. **`DialogBitcostTask.bind(taskID)`** — seeding is **gated to the explicit
   `/task` command** via an optional `seedPrompt?: (prompt: PromptInfo) => void`
   prop. The picker is also opened by the mandatory submit-gate (fires on
   already-typed input) and by session-restore; those callers do NOT pass
   `seedPrompt`, so `bind()` skips the plan fetch entirely and behaves exactly as
   today. When `seedPrompt` is set, after a successful `bindTask` +
   `markBitcostBound`, fetch the latest plan. If `plan?.body` is non-empty:
   - **From-home path** (`created === true`; the session was just created and is
     therefore empty): seed via the existing route mechanism —
     `route.navigate({ type: "session", sessionID, prompt: { input: plan.body, parts: [] } })`.
     The session route already seeds `route.prompt` into the input on mount
     (`routes/session/index.tsx:343-352`), which sidesteps the async
     prompt-mount race.
   - **Existing-session path** (`/task` run inside a session,
     `created === false`): gate on empty session — `sync.data.message[sessionID]`
     absent or length 0 — then call `props.seedPrompt(seed)`. The call-site
     `seedPrompt` impl guards the empty-input condition (don't clobber typed text)
     before `promptRef.current?.set(...)` + `.focus()`.
   - If `plan` is `null` or `body` empty → behave exactly as today (bind, toast,
     navigate with no prompt). No error surfaced.

### Provider-tree constraint (discovered in implementation)

`DialogProvider` is an *ancestor* of `PromptRefProvider` in `app.tsx`, so a dialog
renders **outside** the PromptRef provider — calling `usePromptRef()` inside the
dialog throws "context must be used within a context provider". The prompt-seeding
capability is therefore **injected from the call site** (the `/task` command, where
`promptRef` is in scope) via the `seedPrompt` prop, not resolved in the dialog.
`useSync()` *is* available in the dialog (`SyncProvider` sits above
`DialogProvider`), so the empty-session check stays in the dialog.

### New dialog dependencies

- `useSync()` — to read `sync.data.message[sessionID]` for the empty-session check.
- `seedPrompt` prop (from the `/task` command) — to set the live prompt; the
  command supplies `promptRef`-backed logic and the empty-input guard.

## Data flow

```
/task → DialogBitcostTask → pick/create task
      → bindTask(sessionID, taskID) + markBitcostBound
      → fetchLatestTaskPlan(taskID)            // null-safe
          ├─ created (home):   navigate({session, prompt:{input: body}}) → route seeds on mount
          └─ existing session: if empty session && empty input → promptRef.set({input: body}) + focus
          └─ no plan/body:     bind as today (no prefill)
```

## Error handling

- Plan fetch failure or timeout → `null` → silent skip (bind already succeeded).
- No plan attached → `null` → silent skip.
- Non-empty session or non-empty input (existing-session path) → no prefill.

## Testing

- **Typecheck:** `packages/core`, `opencode`, and `tui` packages = 0 errors
  (`PATH=$HOME/.bun/bin:$PATH; cd packages/<pkg> && bun run typecheck`).
- **Unit (if bitcost-api has a harness):** `fetchLatestTaskPlan` selects the
  newest plan from a multi-version `data` array and returns `null` for empty
  data, missing token, non-OK status, and network failure.
- **Manual TUI e2e** against `BITCOST_URL=https://bitcost.test`:
  1. Home → `/task` → pick a task **with** a plan → new session opens with the
     plan body prefilled and editable.
  2. Home → `/task` → pick a task **without** a plan → new session opens, empty
     input (today's behavior).
  3. In an empty bound session → `/task` → re-pick a task with a plan → input
     prefilled.
  4. In a session with typed-but-unsent text → `/task` → text is **not**
     clobbered.
  5. In a session with existing messages → `/task` → no prefill.

## Out of scope

- Writing user additions back to Bitcost as a new plan version.
- Injecting the plan as hidden/system context (we use the visible editable input).
- Any backend change — the plans endpoint already exists.
