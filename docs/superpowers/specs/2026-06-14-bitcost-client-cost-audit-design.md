# Spec: Report and store the CLI-computed turn cost (client cost audit)

Date: 2026-06-14
Status: Approved

## Problem

On every LLM turn the CLI's core reporter POSTs usage to
`POST /api/tasks/{task}/usage`. The server (`UsageController`) recomputes cost
from token counts via `CostCalculator`, which resolves pricing from the
`model_pricings` table, then the committed models.dev catalog. When neither
source has the model, the turn is stored **unpriced** (`cost_total = null`,
`is_priced = false`) and contributes nothing to the task's cost rollup
(`Task.cost_total = SUM(usages.cost_total)`).

The CLI, however, already has a catalog-computed cost for the turn:
`Step.Ended.cost`. Today the reporter does not send it, so that number is lost
and unpriced turns silently read as $0.

## Goal

Send the CLI-computed turn cost to the server and persist it, so that:

1. Unpriced turns still get a cost (the CLI estimate) and roll up into the task
   total.
2. The CLI estimate is always retained for audit, even when the server has its
   own authoritative price.
3. The provenance of each turn's `cost_total` is explicit and queryable.

## Decisions

- **Merge rule — store both, server preferred.** The client cost is always
  stored as an audit value. `cost_total` uses the server's computed cost when
  the server can price the turn; otherwise it uses the client cost.
- **Provenance — `cost_source` column.** An explicit string distinguishes where
  `cost_total` came from.

## Data model — `usages` table (new columns)

| Column | Type | Meaning |
| --- | --- | --- |
| `client_cost_total` | `decimal(20,10)` nullable | Raw CLI-supplied turn cost. Stored whenever the client sends it, regardless of which cost wins. |
| `cost_source` | `string` nullable | Provenance of `cost_total`: `'pricing'` (model_pricings row) · `'catalog'` (models.dev) · `'client'` (CLI fallback) · `null` (unpriced, no client cost). |

`Task.cost_total` (the `withSum` over `usages.cost_total`) is unchanged — any
non-null `cost_total` rolls up automatically.

## `cost_total` / `cost_source` selection (UsageController)

Let `$server = $calculator->compute(...)` and `$clientCost = $request->input('cost')`.

1. `$server['is_priced'] === true` → `cost_total = $server['cost_total']`,
   `cost_source = $server['source']` (`'pricing'` or `'catalog'`).
2. else if `$clientCost !== null` → `cost_total = $clientCost`,
   `cost_source = 'client'`, `currency = 'USD'`.
3. else → `cost_total = null`, `cost_source = null`.

`client_cost_total = $clientCost` in all branches.

`is_priced` keeps its current meaning (server had authoritative/catalog
pricing); a `'client'`-sourced cost leaves `is_priced = false`.

## CostCalculator change

`compute()` returns an explicit `'source' => 'pricing' | 'catalog' | null` so
the controller does not infer provenance from `pricing_id`:

- `model_pricings` hit → `'pricing'`
- models.dev catalog hit → `'catalog'`
- neither → `null`

## Request / Resource wiring (Laravel)

- `StoreUsageRequest::rules()` adds `'cost' => ['nullable', 'numeric', 'min:0']`.
- `Usage` model: add `client_cost_total`, `cost_source` to `#[Fillable]`; cast
  `client_cost_total => 'decimal:10'`.
- `UsageResource`: expose `cost_source` and `client_cost_total`
  (`client_cost_total` as float-or-null like `cost_total`).
- Migration adds both columns. If a fresh-install schema snapshot exists on the
  Laravel side, update it (mirrors prior practice on the CLI's drizzle snapshot).

## CLI change (`packages/core`)

- `BitcostClient.UsageReport`: add `readonly cost?: number`.
- `reporter.ts`: set `cost: data.cost` on the report (already on `Step.Ended`).
- `client.ts` `reportUsage`: add `cost: report.cost` to the POST body.

## Idempotency

Unchanged: `Usage::firstOrCreate` on `(task_id, idempotency_key)`. A replayed
turn does not overwrite, so cost provenance is stable per turn.

## Testing

**Laravel Feature (`tests/Feature`)** — extend the usage-reporting tests:
- Server-priced turn: `cost_total` = server cost, `cost_source = 'pricing'`
  (or `'catalog'`), `client_cost_total` = the sent client cost.
- Unpriced turn with client cost: `cost_total` = client cost,
  `cost_source = 'client'`, `is_priced = false`.
- Unpriced turn without client cost: `cost_total = null`, `cost_source = null`.
- Idempotent replay does not change stored cost/provenance.

**CLI (`packages/core/test/bitcost-client.test.ts`)**:
- `reportUsage` includes `cost` in the POST body when the report carries one.

## Out of scope

- Recomputing/repricing historical unpriced rows.
- Any UI change in the TUI sidebar (it already renders `Task.cost_total`).
- Sending `request_id` (no source on `Step.Ended`).
