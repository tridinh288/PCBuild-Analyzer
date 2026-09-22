<?php

use App\Exceptions\ConflictException;
use App\Http\Middleware\RejectMalformedJson;
use App\Models\Build;
use App\Models\Category;
use App\Models\Product;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [RejectMalformedJson::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /api error uses the same envelope. No internal details unless APP_DEBUG=true.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Route model binding wraps the "model not found" error in a 404 HTTP exception.
            if ($e instanceof NotFoundHttpException && $e->getPrevious() instanceof ModelNotFoundException) {
                $e = $e->getPrevious();
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error('Dữ liệu không hợp lệ.', 422, $e->errors()),
                $e instanceof ConflictException => ApiResponse::error($e->getMessage(), 409),
                $e instanceof AuthenticationException => ApiResponse::error('Bạn cần đăng nhập để tiếp tục.', 401),
                $e instanceof ModelNotFoundException => ApiResponse::error(match ($e->getModel()) {
                    Build::class => 'Không tìm thấy cấu hình.',
                    Product::class => 'Không tìm thấy linh kiện.',
                    Category::class => 'Không tìm thấy loại linh kiện.',
                    default => 'Không tìm thấy dữ liệu.',
                }, 404),
                $e instanceof NotFoundHttpException => ApiResponse::error('Không tìm thấy dữ liệu.', 404),
                // Keep the exception headers (Retry-After on 429, Allow on 405).
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    match ($e->getStatusCode()) {
                        403 => 'Bạn không có quyền thực hiện thao tác này.',
                        405 => 'Phương thức không được hỗ trợ.',
                        429 => 'Bạn gửi quá nhiều yêu cầu, vui lòng thử lại sau.',
                        default => $e->getMessage() ?: 'Yêu cầu không hợp lệ.',
                    },
                    $e->getStatusCode(),
                )->withHeaders($e->getHeaders()),
                default => ApiResponse::error(
                    'Đã xảy ra lỗi máy chủ.',
                    500,
                    config('app.debug') ? ['exception' => [get_class($e).': '.$e->getMessage()]] : [],
                ),
            };
        });
    })->create();
