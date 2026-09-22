<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ProductService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request, ProductRepositoryInterface $repository): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:32'],
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', Rule::in(['0', '1'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ApiResponse::success(AdminProductResource::collection(
            $repository->paginateForAdmin($filters, (int) ($filters['per_page'] ?? 20)),
        ));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        return ApiResponse::created(new AdminProductResource($this->products->create($request->validated())));
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(new AdminProductResource($product->load('category')));
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(new AdminProductResource($this->products->update($product, $request->validated())));
    }

    public function destroy(Product $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }
}
