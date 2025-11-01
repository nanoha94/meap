<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Auth\Events\Verified;
use App\Http\Requests\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        try {
            // 既に確認済みの場合は早期リターン
            if ($request->user()->hasVerifiedEmail()) {
                return $this->redirectToPlan();
            }

            // メール確認処理を実行
            $this->verifyEmailAndFireEvent($request->user());

            // 成功時のリダイレクト
            return $this->redirectToPlan(true);
        } catch (HttpResponseException $e) {
            // EmailVerificationRequestからのリダイレクトレスポンスをそのまま返す
            throw $e;
        } catch (Exception $e) {
            // 予期しない例外のハンドリング
            return $this->handleVerificationError($e, $request);
        }
    }

    /**
     * メール確認処理とイベント発火
     */
    private function verifyEmailAndFireEvent($user): void
    {
        if (!$user->markEmailAsVerified()) {
            throw new Exception(__('auth.email_verification_failed'));
        }

        event(new Verified($user));
    }

    /**
     * プランページへのリダイレクト
     */
    private function redirectToPlan(bool $verified = false): RedirectResponse
    {
        $url = config('app.frontend_url') . '/plan';

        if ($verified) {
            $url .= '?verified=1';
        }

        return redirect($url);
    }

    /**
     * 検証エラーのハンドリング
     */
    private function handleVerificationError(Throwable $e, $request): RedirectResponse
    {
        $errorType = $this->determineErrorType($e);

        // 詳細ログ出力（内部記録のみ）
        $this->logError($this->getExceptionStatusCode($e), __('operations.auth.email_verification'), $e, $request, [
            'operation' => __('operations.auth.email_verification'),
            'error_type' => $errorType,
            'exception_message' => $e->getMessage(),
            'exception_trace' => $e->getTraceAsString()
        ]);

        // エラータイプのみをフロントエンドに送信
        return redirect(
            config('app.frontend_url') . '/email/verify?error=' . $errorType
        );
    }

    /**
     * 例外の種類に応じてエラータイプを決定
     * 
     * @param Throwable $e
     * @return string
     */
    private function determineErrorType(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return 'database_error';
        }

        if ($e instanceof ValidationException) {
            return 'validation_error';
        }

        // メール確認失敗の場合
        if (str_contains($e->getMessage(), 'email_verification_failed')) {
            return 'verification_failed';
        }

        // デフォルトのエラータイプ
        return 'verification_failed';
    }
}
