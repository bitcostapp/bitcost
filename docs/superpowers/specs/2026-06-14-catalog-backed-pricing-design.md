# Catalog-backed model pricing

## Context

`CostCalculator` prices a usage turn only if a matching row exists in `model_pricings`,
which is seeded from a hand-typed 5-model snapshot (`database/data/model-pricing.php`).
Any other model is stored **unpriced** (`cost_total = null`). That is the "fallback not
working" symptom: the server never consults the model catalog the CLI uses (models.dev).

This adds the full models.dev catalog as a committed JSON snapshot, a `pricing:sync`
command that loads it into `model_pricings`, and a runtime fallback in `CostCalculator`
so a turn is unpriced only when the model is in neither the DB nor the catalog.

Hackathon decision: fetch models.dev **once**, commit it as JSON, and use that JSON for
both the seed/command and the runtime fallback. No live HTTP in the running app.

## Components

### 1. Catalog JSON — `database/data/model-pricing.json`
Generated once from `https://models.dev/api.json`. Every model with token pricing
(`cost.input`/`cost.output`) becomes a row in our shape:
`{ provider, model, variant: null, input_price, output_price, cache_read_price,
cache_write_price, reasoning_price, currency: "USD" }`. Mapping: `cost.input→input_price`,
`cost.output→output_price`, `cost.cache_read→cache_read_price`,
`cost.cache_write→cache_write_price`, `cost.reasoning→reasoning_price` (missing → null).
~4,900 rows. The hand-typed `database/data/model-pricing.php` is deleted.

### 2. Catalog reader — `App\Services\ModelCatalog`
Loads the JSON once (memoized) and exposes
`lookup(string $provider, string $model, ?string $variant = null): ?array` returning the
price row or null. Catalog path from `config('pricing.catalog_path')` (default the JSON
above) so tests can inject a fixture. Single responsibility: read catalog, answer lookups.

### 3. `pricing:sync` command — `App\Console\Commands\SyncPricing`
`php artisan pricing:sync`. Reads the catalog via `ModelCatalog::all()` and
`ModelPricing::updateOrCreate`s each row keyed by
`(provider, model, variant, effective_from=null)`. Idempotent; prints "Synced N models."
`ModelPricingSeeder` calls this command so `db:seed` and the command share one path.

### 4. Runtime fallback — `CostCalculator::compute`
When `ModelPricing::resolve()` is null, call `ModelCatalog::lookup()`. Hit → price from
those rates with `is_priced = true`, `pricing_id = null` (priced from catalog, not a DB
row). Miss → unpriced as today. The calculator stays pure (no DB writes); after
`pricing:sync` the table already holds the catalog, so the fallback is a safety net.

## Testing
- `ModelCatalogTest`: lookup hit/miss against a fixture JSON (via config path override).
- `SyncPricingTest`: `pricing:sync` upserts rows from a fixture; re-run is idempotent.
- `CostCalculatorTest`: no DB row + catalog hit → priced, `pricing_id` null; catalog miss
  → unpriced. Existing `UsageApiTest` "unknown model unpriced" stays valid because
  `mystery-model` is in neither DB nor catalog.

## Out of scope
- Live models.dev fetching from the running app (one-time dev fetch only).
- Persisting catalog-priced models back into `model_pricings` at request time.
- Per-token tiers / audio / context-over-200k pricing (only flat input/output/cache/
  reasoning rates are mapped).
