<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;
use App\Traits\ExceptionHandlerTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    use ExceptionHandlerTrait;

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // AuthenticationExceptionの場合は401を返す
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthenticated'),
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        $operation = $this->determineOperation($request) ?? __('operations.general.unknown');
        $defaultMessage = __('api.general.server_error') ?? $exception->getMessage();
        return $this->handleException($exception, $request, $defaultMessage, $operation);

        return parent::render($request, $exception);
    }

    /**
     * リクエストから操作名を推定
     *
     * @param Request $request
     * @return string
     */
    protected function determineOperation(Request $request): string
    {
        $uri = $request->getRequestUri();

        // 認証関連の操作を判定
        if (str_contains($uri, '/login')) {
            return __('operations.auth.login');
        }
        if (str_contains($uri, '/register')) {
            return __('operations.auth.register');
        }
        if (str_contains($uri, '/password/reset')) {
            return __('operations.auth.password_reset');
        }
        if (str_contains($uri, '/forgot-password')) {
            return __('operations.auth.password_reset_link');
        }

        return __('operations.general.request');
    }
}
