# Task-Plan Prompt Prefill Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When `/task` binds a Bitcost task to an empty session, prefill the prompt input with the task's latest plan body as editable text.

**Architecture:** Add a null-safe `fetchLatestTaskPlan` to the CLI's `bitcost-api.ts` (calls the existing `GET /api/tasks/{task}/plans`). In `DialogBitcostTask.bind()`, after a successful bind, fetch the latest plan and seed the prompt — via the route's `prompt` field for the brand-new-session (home) path, or via the live `PromptRef` for an existing empty session. No backend change; no write-back to Bitcost.

**Tech Stack:** TypeScript, SolidJS (opentui), Bun, `bun:test`. Repo: `bitcost-cli` (cwd `/Users/threeel/code/bitcost/bitcost-cli`).

**Spec:** `/Users/threeel/code/bitcost/bitcost/docs/superpowers/specs/2026-06-13-task-plan-prompt-prefill-design.md`

---

## File Structure

- **Modify** `packages/tui/src/component/bitcost-api.ts` — add `BitcostTaskPlan` type, pure `selectLatestPlan` helper, and `fetchLatestTaskPlan` fetcher. (Mirrors the existing `fetchBitcostTasks` / `createBitcostTask` shape in the same file.)
- **Create** `packages/tui/test/component/bitcost-api.test.ts` — unit test for the pure `selectLatestPlan` selection logic.
- **Modify** `packages/tui/src/component/dialog-bitcost-task.tsx` — add `usePromptRef` + `useSync`, and seed the prompt inside `bind()`.

The fetch/fs/TLS glue in `fetchLatestTaskPlan` mirrors the sibling functions in `bitcost-api.ts`, which have no fetch-level tests; we test the pure selection seam instead. The Solid dialog change has no unit-test seam (component + contexts), so it is verified by typecheck + the spec's manual e2e scenarios — consistent with how `dialog-bitcost-task.tsx` is already covered in this repo.

---

## Task 1: `fetchLatestTaskPlan` in bitcost-api.ts

**Files:**
- Modify: `packages/tui/src/component/bitcost-api.ts`
- Test: `packages/tui/test/component/bitcost-api.test.ts`

- [ ] **Step 1: Write the failing test**

Create `packages/tui/test/component/bitcost-api.test.ts`:

```ts
import { describe, expect, test } from "bun:test"
import { selectLatestPlan, type BitcostTaskPlan } from "../../src/component/bitcost-api"

const plan = (version: number, body = "b"): BitcostTaskPlan => ({ id: version, task_id: 1, body, version })

describe("bitcost-api.selectLatestPlan", () => {
  test("returns null when data is missing", () => {
    expect(selectLatestPlan({})).toBeNull()
  })

  test("returns null when data is empty", () => {
    expect(selectLatestPlan({ data: [] })).toBeNull()
  })

  test("returns the first plan (API returns newest version first)", () => {
    const newest = plan(3)
    expect(selectLatestPlan({ data: [newest, plan(2), plan(1)] })).toBe(newest)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PATH=$HOME/.bun/bin:$PATH; cd packages/tui && bun test test/component/bitcost-api.test.ts`
Expected: FAIL — `selectLatestPlan` is not exported from `bitcost-api` (import/resolve error or "not a function").

- [ ] **Step 3: Add the type, pure helper, and fetcher**

In `packages/tui/src/component/bitcost-api.ts`, append (after the `createBitcostTask` function at the end of the file):

```ts
export interface BitcostTaskPlan {
  id: string | number
  task_id: string | number
  title?: string | null
  body: string
  source?: string
  version?: number
  created_at?: string | null
}

/**
 * Pick the latest plan from a `/api/tasks/{id}/plans` response. The endpoint
 * returns plans newest-version first, so the latest is simply the first row.
 */
export function selectLatestPlan(body: { data?: BitcostTaskPlan[] }): BitcostTaskPlan | null {
  return body.data?.[0] ?? null
}

/**
 * Fetch a task's latest plan. Never throws: returns null when not logged in,
 * on auth/network/timeout failure, or when the task has no plans — a missing
 * plan must never block binding a task.
 */
export async function fetchLatestTaskPlan(taskID: string | number): Promise<BitcostTaskPlan | null> {
  const token = readBitcostToken()
  if (!token) return null
  const base = bitcostBaseUrl()
  const restoreTls = relaxTlsForLocal(base)
  try {
    const res = await bitcostFetch(`${base}/api/tasks/${taskID}/plans`, {
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
      signal: AbortSignal.timeout(10_000),
    })
    if (!res.ok) return null
    const body = (await res.json()) as { data?: BitcostTaskPlan[] }
    return selectLatestPlan(body)
  } catch {
    return null
  } finally {
    restoreTls()
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `PATH=$HOME/.bun/bin:$PATH; cd packages/tui && bun test test/component/bitcost-api.test.ts`
Expected: PASS — 3 tests pass.

- [ ] **Step 5: Typecheck the tui package**

Run: `PATH=$HOME/.bun/bin:$PATH; cd packages/tui && bun run typecheck`
Expected: 0 errors.

- [ ] **Step 6: Commit**

```bash
git add packages/tui/src/component/bitcost-api.ts packages/tui/test/component/bitcost-api.test.ts
git commit -m "feat(bitcost): add fetchLatestTaskPlan to bitcost-api"
```

---

## Task 2: Seed the prompt in DialogBitcostTask.bind()

**Files:**
- Modify: `packages/tui/src/component/dialog-bitcost-task.tsx` (imports near top; `const` decls at top of component ~line 27; `bind()` ~lines 50-87)

This task has no unit-test seam (Solid component wired through `useDialog`/`useSDK`/`useRoute`/`usePromptRef`/`useSync`). It is verified by typecheck (Step 4) and manual e2e (Step 5), matching how this dialog is already covered.

- [ ] **Step 1: Add imports**

In `packages/tui/src/component/dialog-bitcost-task.tsx`, change the `bitcost-api` import to add `fetchLatestTaskPlan` and add two context imports.

Find this line (line 12):

```ts
import { bitcostBaseUrl, createBitcostTask, fetchBitcostTasks, type BitcostTask } from "./bitcost-api"
```

Replace with:

```ts
import { bitcostBaseUrl, createBitcostTask, fetchBitcostTasks, fetchLatestTaskPlan, type BitcostTask } from "./bitcost-api"
import { usePromptRef } from "../context/prompt"
import { useSync } from "../context/sync"
```

- [ ] **Step 2: Resolve the two contexts in the component body**

Find this block (lines 22-28):

```ts
  const dialog = useDialog()
  const { theme } = useTheme()
  const toast = useToast()
  const sdk = useSDK()
  const route = useRoute()
  const local = useLocal()
  const [state, setState] = createSignal<State>({ phase: "loading" })
```

Replace with (adds `promptRef` and `sync`):

```ts
  const dialog = useDialog()
  const { theme } = useTheme()
  const toast = useToast()
  const sdk = useSDK()
  const route = useRoute()
  const local = useLocal()
  const promptRef = usePromptRef()
  const sync = useSync()
  const [state, setState] = createSignal<State>({ phase: "loading" })
```

- [ ] **Step 3: Seed the prompt in `bind()`**

Replace the entire `bind` function (lines 50-87) with:

```ts
  async function bind(taskID: string) {
    try {
      // No session yet (picked a task from home) → create one now, bound to the
      // task. A session only ever exists once it is attributed to a Task.
      let sessionID = props.sessionID
      const created = sessionID === undefined
      if (sessionID === undefined) {
        const agent = local.agent.current()
        const model = local.model.current()
        if (!agent || !model) {
          toast.show({ variant: "error", message: "Choose an agent and model before selecting a task" })
          return
        }
        const res = await sdk.client.session.create({
          agent: agent.name,
          model: {
            providerID: model.providerID,
            id: model.modelID,
            variant: local.model.variant.current(),
          },
          directory: process.cwd(),
        })
        if (!res.data?.id) {
          toast.show({ variant: "error", message: "Failed to start a session" })
          return
        }
        sessionID = res.data.id
      }
      await sdk.client.v2.session.bindTask({ sessionID, taskID })
      markBitcostBound(sessionID, taskID)

      // Pull the task's latest plan into the prompt as an editable seed, but
      // only for an empty session (the "first time"). Never blocks the bind:
      // fetchLatestTaskPlan returns null on any failure or when there is no plan.
      const plan = await fetchLatestTaskPlan(taskID)
      const seed = plan?.body?.trim() ? { input: plan.body, parts: [] } : undefined

      if (created) {
        // A brand-new session is empty by definition; seed via the route, which
        // the session prompt applies on mount (routes/session/index.tsx:343-352).
        if (seed) route.navigate({ type: "session", sessionID, prompt: seed })
        else route.navigate({ type: "session", sessionID })
      } else if (seed) {
        // Existing session: seed only when it has no messages AND the input is
        // currently empty (never clobber text the user already typed).
        const noMessages = (sync.data.message[sessionID] ?? []).length === 0
        const inputEmpty = (promptRef.current?.current.input ?? "") === ""
        if (noMessages && inputEmpty) {
          promptRef.current?.set(seed)
          promptRef.current?.focus()
        }
      }

      toast.show({ variant: "success", message: "Task selected" })
      dialog.clear()
      props.onBound?.()
    } catch {
      toast.show({ variant: "error", message: "Failed to select task" })
    }
  }
```

- [ ] **Step 4: Typecheck the tui package**

Run: `PATH=$HOME/.bun/bin:$PATH; cd packages/tui && bun run typecheck`
Expected: 0 errors.

- [ ] **Step 5: Manual e2e verification**

Start the TUI against the local Bitcost: `BITCOST_URL=https://bitcost.test` (log in via `/login` if prompted). Verify all five scenarios:

1. Home → `/task` → pick a task **with** a plan → new session opens with the plan body prefilled and editable.
2. Home → `/task` → pick a task **without** a plan → new session opens, input empty (unchanged behavior).
3. In an empty bound session → `/task` → re-pick a task with a plan → input is prefilled.
4. In a session with typed-but-unsent text → `/task` → the typed text is **not** clobbered.
5. In a session with existing messages → `/task` → no prefill.

Expected: all five behave as described.

- [ ] **Step 6: Commit**

```bash
git add packages/tui/src/component/dialog-bitcost-task.tsx
git commit -m "feat(bitcost): prefill prompt with task's latest plan on first /task bind"
```

---

## Self-Review Notes

- **Spec coverage:** `fetchLatestTaskPlan` (null-safe) = Task 1; from-home seed via route + existing-session double-gate (no messages AND empty input) + no-plan silent skip = Task 2; all five manual e2e scenarios = Task 2 Step 5. ✓
- **Type consistency:** `BitcostTaskPlan` (Task 1) is the type returned by `fetchLatestTaskPlan` and consumed in Task 2 via `plan?.body`. `seed` is `{ input: string; parts: [] }`, assignable to `PromptInfo` for both `route.navigate({ prompt })` and `promptRef.current.set()`. `promptRef.current?.current.input` matches `PromptRef.current: PromptInfo`. ✓
- **No placeholders.** ✓
