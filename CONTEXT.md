# Bitcost

Bitcost attributes AI spend to the work it was spent on. Enterprises give people
AI accounts (Claude, OpenAI) and the AI bill is one undifferentiated lump.
Developers work through the Bitcost CLI and pick the Task they're on; the CLI
**reports its own per-turn token usage** to Bitcost, which **prices** it — so cost
rolls up per Task and per Department.

## Language

**Task**:
A unit of work, **owned by Bitcost** and scoped to a Department (Team) and User. The
User creates and selects a Task through the Bitcost CLI; a CLI session maps to a Task.
A Task has an `open → completed` lifecycle — once completed it is **locked** (no further
usage accepted) and disappears from the CLI's task list. A Task may carry an optional
external link (Jira/GitHub) for future association, but Bitcost is the system of record.
_Avoid_: ticket, job, work item (when precision matters)

**Epic**:
_(Future.)_ A grouping of Tasks for higher-level rollup. The primary rollup today is
Usage → Task → Department.

**Work Provider**:
_(Future/optional.)_ An external system (Jira, GitHub) a Task may link to via its optional
external link. Not the system of record — Bitcost owns Tasks.
_Avoid_: "provider" unqualified

**AI Provider**:
An external LLM vendor whose API consumes tokens and therefore generates cost —
Anthropic/Claude, OpenAI. The source of *spend*.
_Avoid_: "provider" unqualified

**Usage**:
Tokens consumed by AI Provider requests, attributed to a Task. **Self-reported by the
Bitcost CLI per AI model-turn** (live) and stored raw; Bitcost prices it into Cost.

**Cost**:
Usage converted to currency, **computed server-side by Bitcost** from a pricing table
(per model: input/output/cache/reasoning unit prices). Rolls up Usage → Task → Department.

**Attribution**:
Linking Usage to a Task. *Declared, not inferred* — the User selects the Task in the
Bitcost CLI, which attributes each reported Usage turn to that Task. Bitcost does not use
AI to guess the task from prompt content.

**Bitcost CLI**:
A fork of the opencode coding-agent CLI, distributed by Bitcost. It authenticates to
Bitcost as the User (login is mandatory before prompting), lists and creates the Tasks
available to that User, lets them select the Task they're working on, **reports per-turn
Usage to Bitcost**, marks Tasks complete, and attaches its plans to the Task.
_Avoid_: "the agent", "opencode" (unqualified)

**Bitcost Proxy**:
_(Not implemented; superseded by CLI self-reported Usage.)_ An earlier design routed AI
Provider traffic through a transparent proxy that metered tokens. Metering now happens in
the CLI, which reports Usage directly; there is no proxy in the data path.

**Team**:
The enterprise workspace. Owns Work Provider and AI Provider connections, Users, and
the aggregated Cost view. (Existing model.) A Team may be a **Department** or a
**personal team** — distinguished by the `is_personal` flag.

**Department**:
A non-personal Team (`is_personal = false`) — the business meaning of a Team within an
organization (e.g., Engineering). A **personal team** (`is_personal = true`) is an
individual fallback workspace and is **not** a Department. A User may therefore have no
Department (only a personal team, or no current team at all); consumers must treat
"no Department" as a valid state.

**User**:
A member of a Team who works on Tasks using AI Provider tokens issued through Bitcost.

## Flagged ambiguities

- **"Provider"** — always qualify as **Work Provider** or **AI Provider**. Bare
  "provider" is banned.
- **"Orchestrate" / "distribute tasks"** — Bitcost does NOT assign, route, or
  distribute work between Users. This was an early framing that was explicitly rejected.
  Copy must not imply workflow features. (Bitcost *does* create and complete Tasks as
  cost-attribution units, created by the User in the CLI — that is not work distribution.)
- **"Proxy meters the tokens"** — outdated. There is no proxy; the CLI self-reports
  Usage and Bitcost prices it.
- **"Infer the task" / "AI reads your prompts"** — rejected. Attribution is declared
  by the User selecting a Task in the Bitcost CLI, never guessed from prompt content.
  Copy must not imply prompt inspection for classification.
