<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;
use App\Traits\ExceptionHandlerTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
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
        // throttle 等が返すレスポンス付き例外はそのまま返す
        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        }

        // AuthenticationExceptionの場合は401を返す
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => __('auth.general.unauthenticated'),
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        if ($exception instanceof InvalidSignatureException && $this->isEmailVerificationVerifyRoute($request)) {
            if (!$request->expectsJson()) {
                return $this->redirectToEmailVerifyWithError('invalid_link');
            }
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
        if (str_contains($uri, '/email/verify/')) {
            return __('operations.auth.email_verification');
        }

        return __('operations.general.request');
    }

    /**
     * verification.verify ルートかどうかを判定
     */
    protected function isEmailVerificationVerifyRoute(Request $request): bool
    {
        return $request->route()?->getName() === 'verification.verify';
    }

    /**
     * メール確認ページへエラータイプ付きでリダイレクト
     */
    protected function redirectToEmailVerifyWithError(string $errorType): RedirectResponse
    {
        return redirect(config('app.frontend_url') . '/email/verify?error=' . $errorType);
    }
}
