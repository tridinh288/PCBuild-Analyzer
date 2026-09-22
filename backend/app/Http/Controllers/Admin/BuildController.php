<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BuildPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BuildItemsRequest;
use App\Http\Requests\Admin\BuildRequest;
use App\Http\Resources\BuildResource;
use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use App\Services\BuildService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BuildController extends Controller
{
    public function __construct(private readonly BuildService $builds) {}

    public function index(Request $request, BuildRepositoryInterface $repository): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', Rule::enum(BuildPurpose::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ApiResponse::success(BuildResource::collection(
            $repository->search($filters, null, (int) ($filters['per_page'] ?? 20)),
        ));
    }

    public function store(BuildRequest $request): JsonResponse
    {
        $build = $this->builds->create($request->validated());

        return ApiResponse::created($this->payload($build));
    }

    public function show(Build $build): JsonResponse
    {
        return ApiResponse::success($this->payload($build));
    }

    public function update(BuildRequest $request, Build $build): JsonResponse
    {
        return ApiResponse::success($this->payload($this->builds->update($build, $request->validated())));
    }

    public function destroy(Build $build): Response
    {
        $this->builds->delete($build);

        return response()->noContent();
    }

    public function items(BuildItemsRequest $request, Build $build): JsonResponse
    {
        return ApiResponse::success($this->payload($this->builds->syncItems($build, $request->validated('items'))));
    }

    /**
     * The build with its items and total, plus the engine's analysis for live feedback.
     *
     * @return array<string, mixed>
     */
    private function payload(Build $build): array
    {
        $fresh = Build::query()->withTotalPrice()->with('items.product.category')->findOrFail($build->id);

        return [
            'build' => (new BuildResource($fresh))->resolve(),
            'image_public_id' => $fresh->image_public_id,
            'analysis' => $this->builds->analyze($fresh)->toArray(),
        ];
    }
}
