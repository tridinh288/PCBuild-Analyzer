<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Hardware\SpecSchema;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * List and edit only: no create or delete (D-006). No service layer here — a Form Request
 * and one Eloquent update are the whole use case.
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return ApiResponse::success(new CategoryResource($category));
    }

    public function specSchema(Category $category, SpecSchema $schema): JsonResponse
    {
        return ApiResponse::success([
            'category' => $category->slug,
            'fields' => $schema->formSchema($category->slug),
        ]);
    }
}
