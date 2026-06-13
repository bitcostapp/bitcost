<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PricingResource;
use App\Models\ModelPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * Resolve the effective pricing rates (per 1,000,000 tokens) for a model,
     * preferring a variant-specific row, then the base model. Returns
     * { "data": null } when no pricing is configured so the caller (the CLI) can
     * fall back to its local catalog rate.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string'],
            'model' => ['required', 'string'],
            'variant' => ['nullable', 'string'],
        ]);

        $pricing = ModelPricing::resolve(
            $validated['provider'],
            $validated['model'],
            $validated['variant'] ?? null,
        );

        if ($pricing === null) {
            return response()->json(['data' => null]);
        }

        return PricingResource::make($pricing)->response();
    }
}
