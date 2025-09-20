<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

trait LoggingTrait
{
    /**
     * 統一された情報ログを記録
     * 
     * @param HttpStatusCode $statusCode ステータスコード
     * @param string $operation 実行中の操作
     * @param string $message 情報メッセージ
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    public function logInfo(
        HttpStatusCode $statusCode,
        string $operation,
        string $message,
        Request $request,
        array $additionalContext = [],
        string $callerMethod = __METHOD__
    ): void {
        $this->logMessage('info', $operation, $message, $request, $statusCode->value, $additionalContext, $callerMethod);
    }

    /**
     * 統一された警告ログを記録
     * 
     * @param HttpStatusCode $statusCode ステータスコード
     * @param string $operation 実行中の操作
     * @param string $message 警告メッセージ
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    public function logWarning(
        HttpStatusCode $statusCode,
        string $operation,
        string $message,
        Request $request,
        array $additionalContext = [],
        string $callerMethod = __METHOD__
    ): void {
        $this->logMessage('warning', $operation, $message, $request, $statusCode->value, $additionalContext, $callerMethod);
    }

    /**
     * 統一されたエラーログを記録
     *
     * @param HttpStatusCode $statusCode ステータスコード
     * @param string $operation 実行中の操作
     * @param Exception $exception 発生した例外
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    public function logError(
        HttpStatusCode | int $statusCode,
        string $operation,
        Exception $exception,
        Request $request,
        array $additionalContext = [],
        string $callerMethod = __METHOD__
    ): void {
        $errorContext = array_merge([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'model' => $this->getExceptionModel($exception),
            'error_message' => $exception->getMessage(),
            'errors' => method_exists($exception, 'errors') ? $exception->errors() : null,
            'trace' => $exception->getTraceAsString(),
        ], $additionalContext);

        if ($statusCode instanceof HttpStatusCode) {
            $statusCode = $statusCode->value;
        }

        $this->logMessage('error', $operation, __('api.general.error'), $request, $statusCode, $errorContext, $callerMethod);
    }

    /**
     * 統一されたログを記録（内部実装）
     *
     * @param string $logLevel ログレベル
     * @param string $operation 実行中の操作
     * @param string $message ログメッセージ
     * @param Request $request リクエストインスタンス
     * @param int $httpStatusCode HTTPステータスコード
     * @param array $additionalContext 追加のコンテキスト情報
     */
    private function logMessage(
        string $logLevel,
        string $operation,
        string $message,
        Request $request,
        int $statusCode,
        array $additionalContext = [],
        string $callerMethod,
    ): void {
        $user = $request->user();
        $group = $user?->group;

        $context = array_merge([
            'method' => $callerMethod,
            'user_id' => $user?->id,
            'group_id' => $group?->id,
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $statusCode,

        ], $additionalContext);

        // 機密情報を除外
        $context = $this->filterSensitiveData($context, $request);

        Log::$logLevel("操作「{$operation}」: {$message}", $context);
    }

    /**
     * 機密情報をフィルタリング
     *
     * @param array $context ログコンテキスト
     * @param Request $request リクエストインスタンス
     * @return array フィルタリングされたコンテキスト
     */
    private function filterSensitiveData(array $context, Request $request): array
    {
        // 実際のプロジェクトで使用されている機密フィールドのみを含める
        $sensitiveFields = [
            'password',           // ユーザーパスワード
            'password_confirmation', // パスワード確認
            'current_password',   // 現在のパスワード
            'token',             // 認証トークン
            'api_token',         // APIトークン
            'api_key',           // APIキー
            'secret'             // シークレットキー
        ];

        // リクエストデータから機密情報を除外
        $requestData = $request->all();
        foreach ($sensitiveFields as $field) {
            if (isset($requestData[$field])) {
                $requestData[$field] = '*****';
            }
        }

        $context['request_data'] = $requestData;

        return $context;
    }

    /**
     * 例外のステータスコードを取得
     */
    private function getLoggingExceptionStatusCode(Exception $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            try {
                return $exception->getStatusCode();
            } catch (\ValueError $e) {
                // 無効なステータスコードの場合は500を返す
                return HttpStatusCode::INTERNAL_SERVER_ERROR->value;
            }
        }
        return HttpStatusCode::INTERNAL_SERVER_ERROR->value;
    }

    /**
     * 例外に関連するモデルクラス名を取得
     */
    private function getExceptionModel(Exception $exception): ?string
    {
        // getModel()メソッドが存在する場合
        if (method_exists($exception, 'getModel')) {
            return $exception->getModel();
        }

        return null;
    }
}
