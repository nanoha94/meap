<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

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
     * @param string $callerMethod 呼び出し元のメソッド名
     */
    public function logInfo(
        HttpStatusCode $statusCode,
        string $operation,
        string $message,
        Request $request,
        array $additionalContext = [],
        string $callerMethod = 'unknown',
    ): void {
        $this->logMessage('info', $operation, $message, $request, $statusCode->value, $additionalContext, $callerMethod);
    }

    /**
     * 統一された警告ログを記録
     *
     * @param string $operation 実行中の操作
     * @param string $message 警告メッセージ
     * @param array $additionalContext 追加のコンテキスト情報
     * @param string $callerMethod 呼び出し元のメソッド名
     */
    public function logWarning(
        string $operation,
        string $message,
        array $additionalContext = [],
        string $callerMethod = 'unknown',
    ): void {
        $this->logMessage('warning', $operation, $message, null, null, $additionalContext, $callerMethod);
    }

    /**
     * 統一されたエラーログを記録
     *
     * @param HttpStatusCode|int $statusCode ステータスコード
     * @param string $operation 実行中の操作
     * @param \Throwable $exception 発生した例外（ExceptionまたはError）
     * @param Request $request リクエストインスタンス
     * @param array $additionalContext 追加のコンテキスト情報
     */
    public function logError(
        HttpStatusCode | int $statusCode,
        string $operation,
        Throwable $exception,
        Request $request,
        array $additionalContext = [],
    ): void {

        $errorContext = array_merge(
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'model' => $this->getExceptionModel($exception),
                'errors' => method_exists($exception, 'errors') ? $exception->errors() : [],
            ],
            $additionalContext,
            ['trace' => $exception->getTraceAsString()]
        );


        if ($statusCode instanceof HttpStatusCode) {
            $httpStatusCode = $statusCode;
        }
        // int型の場合はHttpStatusCodeにキャストを試行
        else {
            $httpStatusCode = HttpStatusCode::tryFrom($statusCode);
        }

        if ($httpStatusCode !== null) {
            $errorCode = $httpStatusCode->value;
            $errorMessage = $httpStatusCode->getDescription() ?? __('api.general.error');
        }
        // 無効なステータスコードの場合はそのまま使用
        else {
            $errorCode = $statusCode;
            $errorMessage = __('api.general.error');
        }

        $callerMethod = $this->getCallerMethod($exception);


        $this->logMessage('error', $operation, $errorMessage, $request, $errorCode, $errorContext, $callerMethod);
    }

    /**
     * 統一されたログを記録（内部実装）
     *
     * @param string $logLevel ログレベル
     * @param string $operation 実行中の操作
     * @param string $message ログメッセージ
     * @param Request|null $request リクエストインスタンス
     * @param int|null $statusCode HTTPステータスコード
     * @param array $additionalContext 追加のコンテキスト情報
     * @param string $callerMethod 呼び出し元のメソッド名
     */
    private function logMessage(
        string $logLevel,
        string $operation,
        string $message,
        Request | null $request = null,
        int | null $statusCode = null,
        array $additionalContext = [],
        string $callerMethod = 'unknown',
    ): void {
        $user = $request?->user();
        $group = null;

        // $userがUserモデルのインスタンスで、groups()メソッドが存在する場合のみ取得
        if ($user && method_exists($user, 'groups')) {
            try {
                $group = $user->groups()->first();
            } catch (\Exception $e) {
                // groups()の呼び出しに失敗した場合は無視
                $group = null;
            }
        }

        $context = [
            'method' => $callerMethod,
            'user_id' => $user?->id,
            'group_id' => $group?->id,
            'request_method' => $request?->method(),
            'request_url' => $request?->fullUrl(),
            'request_ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'status_code' => $statusCode,
        ];

        // 機密情報を除外
        if ($request) {
            $context = $this->filterSensitiveData($context, $request);
        }
        $context = array_merge($context, $additionalContext);

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
    private function getLoggingExceptionStatusCode(Throwable $exception): int
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
    private function getExceptionModel(Throwable $exception): ?string
    {
        // getModel()メソッドが存在する場合
        if (method_exists($exception, 'getModel')) {
            return $exception->getModel();
        }

        return null;
    }


    /**
     * 実際にエラーが発生したメソッドを特定
     *
     * @return string
     */
    private function getCallerMethod(Exception|Error $e): string
    {
        $trace = $e->getTrace();

        // スキップするクラスとメソッド
        $skipClasses = [
            'App\Exceptions\Handler',
            'App\Traits\LoggingTrait',
            'App\Traits\ExceptionHandlerTrait',
            'App\Traits\ApiResponse'
        ];

        $skipMethods = [
            'render',
            'logError',
            'handleException',
            'getCallerMethod',
            'handleGenericException',
            '_mockery_handleMethodCall',
            '__call',
            '__callStatic',
            '{closure}',
            '__callClosure'
        ];

        // モック関連のクラス名パターン
        $mockPatterns = [
            'Mockery_',
            'Mock_',
            'Mockery\\',
            'Mock\\'
        ];

        // バリデーション例外の場合、FormRequestクラスを優先的に探す
        if ($e instanceof ValidationException) {
            foreach ($trace as $frame) {
                if (isset($frame['class']) && isset($frame['function'])) {
                    $class = $frame['class'];
                    $function = $frame['function'];

                    // モック関連のクラスをスキップ
                    if ($this->isMockClass($class, $mockPatterns)) {
                        continue;
                    }

                    if (str_contains($class, 'Request')) {
                        $shortClassName = class_basename($class);
                        return "{$shortClassName}::{$function}";
                    }
                }
            }
        }

        // アプリケーションコードのメソッドを探す（簡素化）
        foreach ($trace as $frame) {
            if (isset($frame['class']) && isset($frame['function'])) {
                $class = $frame['class'];
                $function = $frame['function'];

                // モック関連のクラスをスキップ
                if ($this->isMockClass($class, $mockPatterns)) {
                    continue;
                }

                // スキップするクラスでない場合
                if (!in_array($class, $skipClasses) && !str_contains($class, 'Trait')) {
                    // アプリケーションコードかチェック
                    if (str_contains($class, 'App\\') && !in_array($function, $skipMethods)) {
                        $shortClassName = class_basename($class);
                        return "{$shortClassName}::{$function}";
                    }
                }
            }
        }

        // フォールバック: ファイル名と行番号
        if (isset($trace[0]['file']) && isset($trace[0]['line'])) {
            $file = basename($trace[0]['file'], '.php');
            $line = $trace[0]['line'];
            return "{$file}::line_{$line}";
        }

        return 'unknown';
    }

    /**
     * モッククラスかどうかを判定
     *
     * @param string $className クラス名
     * @param array $mockPatterns モック関連のパターン
     * @return bool
     */
    private function isMockClass(string $className, array $mockPatterns): bool
    {
        foreach ($mockPatterns as $pattern) {
            if (str_contains($className, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * バリデーション例外からFormRequestクラスを特定
     *
     * @param \Illuminate\Validation\ValidationException $exception
     * @return string|null
     */
    private function getFormRequestClass(\Illuminate\Validation\ValidationException $exception): ?string
    {
        $trace = $exception->getTrace();

        foreach ($trace as $frame) {
            if (isset($frame['class']) && str_contains($frame['class'], 'Request')) {
                return class_basename($frame['class']);
            }
        }

        return null;
    }
}
