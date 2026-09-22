<?php

namespace App\Http\Controllers\Api;

use App\Domain\Analysis\BuildAnalyzer;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalysisRequest;
use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use App\Services\BuildConfigurationFactory;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    /**
     * Full analysis of a template. Default profile = the template's purpose (D-016).
     */
    public function show(
        string $slug,
        AnalysisRequest $request,
        BuildRepositoryInterface $builds,
        BuildConfigurationFactory $configurations,
        BuildAnalyzer $analyzer,
    ): JsonResponse {
        $build = $builds->findBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Build::class, [$slug]);

        $profile = $request->profile() ?? $build->purpose;
        $analysis = $analyzer->analyze($configurations->fromBuild($build), $profile);

        return ApiResponse::success($analysis->toArray(), ['build' => $build->slug, 'profile' => $profile->value]);
    }
}
