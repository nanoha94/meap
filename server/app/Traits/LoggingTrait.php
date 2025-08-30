<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

trait LoggingTrait
{
    /**
     * 統一されたログを記録（汎用メソッド）
     *
     * @param string $logLevel ログレベル（info, warning, debug, error）
     * @param string $operation 実行中の操作
     * @param string $message ログメッセージ
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    protected function logMessage(
        string $logLevel,
        string $operation,
        string $message,
        Request $request,
        array $additionalContext = []
    ): void {
        $user = $request->user();
        $group = $user?->group;

        $context = array_merge([
            'operation' => $operation,
            'controller' => class_basename($this),
            'method' => debug_backtrace()[1]['function'] ?? 'unknown',
            'user_id' => $user?->id,
            'group_id' => $group?->id,
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], $additionalContext);

        // 機密情報を除外
        $context = $this->filterSensitiveData($context, $request);

        Log::$logLevel("操作「{$operation}」: {$message}", $context);
    }

    /**
     * 統一されたエラーログを記録
     *
     * @param string $operation 実行中の操作
     * @param Exception $exception 発生した例外
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    protected function logError(
        string $operation,
        Exception $exception,
        Request $request,
        array $additionalContext = []
    ): void {
        $errorContext = array_merge([
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'status_code' => $this->getExceptionStatusCode($exception),
            'model' => $this->getExceptionModel($exception),
        ], $additionalContext);

        $this->logMessage('error', $operation, 'エラーが発生しました', $request, $errorContext);
    }

    /**
     * 統一された警告ログを記録
     *
     * @param string $operation 実行中の操作
     * @param string $message 警告メッセージ
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    protected function logWarning(
        string $operation,
        string $message,
        Request $request,
        array $additionalContext = []
    ): void {
        $this->logMessage('warning', $operation, $message, $request, $additionalContext);
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
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];

        // リクエストデータから機密情報を除外
        $requestData = $request->all();
        foreach ($sensitiveFields as $field) {
            if (isset($requestData[$field])) {
                $requestData[$field] = '[FILTERED]';
            }
        }

        $context['request_data'] = $requestData;

        return $context;
    }

    /**
     * 例外のステータスコードを取得
     */
    private function getExceptionStatusCode(Exception $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            try {
                return $exception->getStatusCode();
            } catch (\ValueError $e) {
                // 無効なステータスコードの場合は500を返す
                return 500;
            }
        }
        return 500;
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
