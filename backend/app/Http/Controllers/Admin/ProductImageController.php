<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageRequest;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Separate POST endpoint for uploads: PHP does not parse multipart bodies on PUT.
 */
class ProductImageController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function store(ImageRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(new AdminProductResource($this->products->replaceImage($product, $request->file('image'))));
    }

    public function destroy(Product $product): JsonResponse
    {
        return ApiResponse::success(new AdminProductResource($this->products->removeImage($product)));
    }
}
