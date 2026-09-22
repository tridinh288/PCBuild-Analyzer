<?php

namespace App\Support\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The single JSON envelope used by every API response (spec section 27):
 *   { "success": true,  "data": ..., "meta": {...} }
 *   { "success": false, "message": "...", "errors": {...} }
 */
final class ApiResponse
{
    // Vietnamese text as UTF-8 instead of \uXXXX escapes: readable and smaller.
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE;

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $meta = [...self::pagination($data->resource), ...$meta];
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        $body = ['success' => true, 'data' => $data];

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status, [], self::JSON_FLAGS);
    }

    public static function created(mixed $data): JsonResponse
    {
        return self::success($data, status: 201);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status, [], self::JSON_FLAGS);
    }

    /**
     * @return array<string, int>
     */
    private static function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
