<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUsageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:128'],
            'session' => ['nullable', 'string', 'max:255'],
            'request_id' => ['nullable', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'tokens' => ['required', 'array'],
            'tokens.input' => ['nullable', 'integer', 'min:0'],
            'tokens.output' => ['nullable', 'integer', 'min:0'],
            'tokens.reasoning' => ['nullable', 'integer', 'min:0'],
            'tokens.cache' => ['nullable', 'array'],
            'tokens.cache.read' => ['nullable', 'integer', 'min:0'],
            'tokens.cache.write' => ['nullable', 'integer', 'min:0'],
            'reported_at' => ['nullable', 'date'],
        ];
    }

    /**
     * The token counts flattened to the columns stored on a usage row.
     *
     * @return array{input: int, output: int, reasoning: int, cache_read: int, cache_write: int}
     */
    public function tokenCounts(): array
    {
        return [
            'input' => (int) $this->input('tokens.input', 0),
            'output' => (int) $this->input('tokens.output', 0),
            'reasoning' => (int) $this->input('tokens.reasoning', 0),
            'cache_read' => (int) $this->input('tokens.cache.read', 0),
            'cache_write' => (int) $this->input('tokens.cache.write', 0),
        ];
    }
}
