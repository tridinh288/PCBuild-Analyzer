<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ComponentController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function index(ComponentIndexRequest $request): JsonResponse
    {
        $page = $this->products->paginate(
            $request->filters(),
            $request->validated('sort'),
            (int) $request->validated('per_page', 12),
        );

        return ApiResponse::success(ProductResource::collection($page));
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->products->findBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Product::class, [$slug]);

        return ApiResponse::success(new ProductResource($product));
    }
}
