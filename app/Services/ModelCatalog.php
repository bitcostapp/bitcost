<?php

namespace App\Services;

use RuntimeException;

/**
 * Reads the committed models.dev pricing snapshot and answers per-model lookups.
 *
 * @phpstan-type CatalogRow array{
 *     provider: string,
 *     model: string,
 *     variant: string|null,
 *     input_price: float,
 *     output_price: float,
 *     cache_read_price: float|null,
 *     cache_write_price: float|null,
 *     reasoning_price: float|null,
 *     currency: string,
 * }
 */
class ModelCatalog
{
    /**
     * The catalog rows, loaded lazily from the JSON snapshot.
     *
     * @var list<CatalogRow>|null
     */
    private ?array $rows = null;

    /**
     * Lookup index keyed by "provider\0model\0variant".
     *
     * @var array<string, CatalogRow>|null
     */
    private ?array $index = null;

    public function __construct(private readonly string $path) {}

    /**
     * Resolve a model's pricing, preferring a variant-specific entry then the
     * base model. Returns null when the catalog has no rates for the model.
     *
     * @return CatalogRow|null
     */
    public function lookup(string $provider, string $model, ?string $variant = null): ?array
    {
        $index = $this->index();

        if ($variant !== null) {
            $match = $index[$this->key($provider, $model, $variant)] ?? null;

            if ($match !== null) {
                return $match;
            }
        }

        return $index[$this->key($provider, $model, null)] ?? null;
    }

    /**
     * All catalog rows.
     *
     * @return list<CatalogRow>
     */
    public function all(): array
    {
        return $this->rows ??= $this->load();
    }

    /**
     * Build (and memoize) the lookup index.
     *
     * @return array<string, CatalogRow>
     */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = [];

        foreach ($this->all() as $row) {
            $index[$this->key($row['provider'], $row['model'], $row['variant'])] = $row;
        }

        return $this->index = $index;
    }

    private function key(string $provider, string $model, ?string $variant): string
    {
        return $provider."\0".$model."\0".($variant ?? '');
    }

    /**
     * Load and decode the catalog snapshot.
     *
     * @return list<CatalogRow>
     */
    private function load(): array
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("Model pricing catalog not found at [{$this->path}].");
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Model pricing catalog at [{$this->path}] is not valid JSON.");
        }

        $rows = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $rows[] = [
                'provider' => (string) ($entry['provider'] ?? ''),
                'model' => (string) ($entry['model'] ?? ''),
                'variant' => isset($entry['variant']) ? (string) $entry['variant'] : null,
                'input_price' => (float) ($entry['input_price'] ?? 0),
                'output_price' => (float) ($entry['output_price'] ?? 0),
                'cache_read_price' => isset($entry['cache_read_price']) ? (float) $entry['cache_read_price'] : null,
                'cache_write_price' => isset($entry['cache_write_price']) ? (float) $entry['cache_write_price'] : null,
                'reasoning_price' => isset($entry['reasoning_price']) ? (float) $entry['reasoning_price'] : null,
                'currency' => (string) ($entry['currency'] ?? 'USD'),
            ];
        }

        return $rows;
    }
}
