# Bitcost

Bitcost attributes AI spend to the work it was spent on. Enterprises give people
AI accounts (Claude, OpenAI) and assign them tasks in Jira/GitHub, but the AI bill
is one undifferentiated lump. Developers work through the Bitcost CLI and pick the
task they're on; Bitcost transparently proxies their AI traffic, meters the tokens,
and converts them to currency — so cost rolls up per task and per epic.

## Language

**Task**:
A unit of work assigned to a person, owned in an external Work Provider (a Jira issue,
a GitHub issue). Bitcost reads tasks; it never creates or assigns them.
_Avoid_: ticket, job, work item (when precision matters)

**Epic**:
A grouping of Tasks in the Work Provider. Cost rolls up Task → Epic.

**Work Provider**:
An external system that holds Tasks and Epics — Jira, GitHub. The source of *what*
work exists.
_Avoid_: "provider" unqualified

**AI Provider**:
An external LLM vendor whose API consumes tokens and therefore generates cost —
Anthropic/Claude, OpenAI. The source of *spend*.
_Avoid_: "provider" unqualified

**Usage**:
Tokens consumed by AI Provider requests, attributed to a Task.

**Cost**:
Usage converted to currency.

**Attribution**:
Linking a request's Usage to a Task. *Declared, not inferred* — the User selects the
Task in the Bitcost CLI, which stamps each AI request with task headers. Bitcost does
not use AI to guess the task from prompt content.

**Bitcost CLI**:
A fork of the opencode coding-agent CLI, distributed by Bitcost. It authenticates to
Bitcost as the User, lists the Tasks available to that User, lets them select the Task
they're working on, stamps outgoing AI requests with task headers, and routes them
through the Bitcost Proxy.
_Avoid_: "the agent", "opencode" (unqualified)

**Bitcost Proxy**:
The transparent proxy that AI Provider traffic flows through. It asynchronously
captures each request and computes Cost from the model and token counts, attributing
Usage to the stamped Task.

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
  distribute work. This was an early framing that was explicitly rejected. Copy must
  not imply task management or workflow features.
- **"Infer the task" / "AI reads your prompts"** — rejected. Attribution is declared
  by the User selecting a Task in the Bitcost CLI, never guessed from prompt content.
  Copy must not imply prompt inspection for classification.
