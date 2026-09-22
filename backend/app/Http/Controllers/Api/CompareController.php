<?php

namespace App\Http\Controllers\Api;

use App\Enums\BuildPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompareRequest;
use App\Services\ComparisonService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/compare. Facts and differences only — never a winner (D-017).
 */
class CompareController extends Controller
{
    public function __invoke(CompareRequest $request, ComparisonService $comparison): JsonResponse
    {
        $profile = $request->profile() ?? BuildPurpose::GeneralUse;
        $result = $comparison->compare($request->validated('configurations'), $profile);

        return ApiResponse::success($result['comparison']->toArray(), [
            'profile' => $profile->value,
            'missing' => $result['missing'],
        ]);
    }
}
