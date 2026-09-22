<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageRequest;
use App\Http\Resources\BuildResource;
use App\Models\Build;
use App\Services\BuildService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class BuildImageController extends Controller
{
    public function __construct(private readonly BuildService $builds) {}

    public function store(ImageRequest $request, Build $build): JsonResponse
    {
        return ApiResponse::success(new BuildResource($this->builds->replaceImage($build, $request->file('image'))));
    }

    public function destroy(Build $build): JsonResponse
    {
        return ApiResponse::success(new BuildResource($this->builds->removeImage($build)));
    }
}
