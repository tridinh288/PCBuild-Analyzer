<?php

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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /api error uses the same envelope. No internal details unless APP_DEBUG=true.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error('Dữ liệu không hợp lệ.', 422, $e->errors()),
                $e instanceof AuthenticationException => ApiResponse::error('Bạn cần đăng nhập để tiếp tục.', 401),
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiResponse::error('Không tìm thấy dữ liệu.', 404),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    match ($e->getStatusCode()) {
                        403 => 'Bạn không có quyền thực hiện thao tác này.',
                        405 => 'Phương thức không được hỗ trợ.',
                        429 => 'Bạn gửi quá nhiều yêu cầu, vui lòng thử lại sau.',
                        default => $e->getMessage() ?: 'Yêu cầu không hợp lệ.',
                    },
                    $e->getStatusCode(),
                ),
                default => ApiResponse::error(
                    'Đã xảy ra lỗi máy chủ.',
                    500,
                    config('app.debug') ? ['exception' => [get_class($e).': '.$e->getMessage()]] : [],
                ),
            };
        });
    })->create();
