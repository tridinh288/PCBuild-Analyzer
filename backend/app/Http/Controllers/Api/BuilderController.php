<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuilderAnalyzeRequest;
use App\Http\Requests\BuilderOptionsRequest;
use App\Http\Resources\BuilderOptionResource;
use App\Http\Resources\ProductResource;
use App\Services\BuilderService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Stateless Builder endpoints: POST because the body carries a configuration; nothing is
 * written to the database (spec section 27, D-019).
 */
class BuilderController extends Controller
{
    public function __construct(private readonly BuilderService $builder) {}

    public function options(BuilderOptionsRequest $request): JsonResponse
    {
        $result = $this->builder->optionsFor(
            $request->validated('category'),
            $request->validated('selected'),
            $request->validated('filters') ?? [],
            (bool) $request->validated('compatible_only', false),
            $request->validated('sort'),
        );

        return ApiResponse::success(BuilderOptionResource::collection($result['options']), [
            'category' => $request->validated('category'),
            'total' => count($result['options']),
            'missing' => $result['missing'],
        ]);
    }

    public function analyze(BuilderAnalyzeRequest $request): JsonResponse
    {
        $result = $this->builder->analyze($request->validated('selected'), $request->profile());

        $items = array_map(fn (array $item) => [
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'product' => new ProductResource($item['product']),
        ], $result['items']);

        return ApiResponse::success([...$result['analysis']->toArray(), 'items' => $items], [
            'profile' => $result['analysis']->performance->profile->value,
            'missing' => $result['missing'],
        ]);
    }
}
