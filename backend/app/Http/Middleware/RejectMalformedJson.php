<?php

namespace App\Http\Middleware;

use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel silently treats an unparsable JSON body as empty, which would surface as a
 * confusing "field required" 422. A syntax error in the body is a 400 instead.
 */
class RejectMalformedJson
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson() && trim($request->getContent()) !== '') {
            json_decode($request->getContent());

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ApiResponse::error('Dữ liệu JSON gửi lên không hợp lệ.', 400);
            }
        }

        return $next($request);
    }
}
