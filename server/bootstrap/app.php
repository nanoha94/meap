<?php

use App\Exceptions\Handler;
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
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);

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
        $middleware->prepend(\App\Http\Middleware\BasicAuth::class);
        $middleware->trustProxies(at: '*');

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // カスタム例外ハンドラーでレンダリング処理を上書き
        $exceptions->render(function (Throwable $exception, \Illuminate\Http\Request $request) {
            $handler = new Handler(app());
            return $handler->render($request, $exception);
        });
    })->create();
