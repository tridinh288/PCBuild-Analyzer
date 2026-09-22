<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CatalogFilterService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(CategoryResource::collection(Category::orderBy('sort_order')->get()));
    }

    public function filters(Category $category, CatalogFilterService $filters): JsonResponse
    {
        return ApiResponse::success($filters->forCategory($category->slug));
    }
}
