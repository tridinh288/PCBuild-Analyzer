<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuildIndexRequest;
use App\Http\Resources\BuildResource;
use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class BuildController extends Controller
{
    public function __construct(private readonly BuildRepositoryInterface $builds) {}

    public function index(BuildIndexRequest $request): JsonResponse
    {
        $page = $this->builds->search(
            $request->filters(),
            $request->validated('sort'),
            (int) $request->validated('per_page', 12),
        );

        return ApiResponse::success(BuildResource::collection($page));
    }

    public function show(string $slug): JsonResponse
    {
        $build = $this->builds->findBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Build::class, [$slug]);

        return ApiResponse::success(new BuildResource($build));
    }
}
