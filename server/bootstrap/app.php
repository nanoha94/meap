<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        // メール認証済みユーザーのみアクセス可能なミドルウェアを登録
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->trustProxies(at: '*');

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 本番環境では詳細なエラー情報を隠す
        if (app()->environment('production')) {
            $exceptions->report(function (\Throwable $e) {
                // 本番環境でのエラーログ記録
                \Illuminate\Support\Facades\Log::error('Unhandled exception: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            });
        }

        // APIレスポンス用の例外ハンドラー
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => app()->environment('production')
                        ? 'サーバーエラーが発生しました'
                        : $e->getMessage(),
                    'error_code' => 500,
                    'error_description' => 'サーバー内部エラーが発生しました'
                ], 500);
            }
        });
    })->create();
