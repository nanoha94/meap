<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

trait ExceptionHandlerTrait
{
    use ApiResponse;

    /**
     * 基本的な例外処理（汎用的）
     * 例外の種類に応じて適切なレスポンスを返す
     */
    protected function handleException(
        Exception $e,
        Request $request,
        ?string $defaultMessage = null,
        ?string $operation = null,
        array $additionalContext = []
    ): JsonResponse {
        $defaultMessage = $defaultMessage ?? __('api.general.error');

        // バリデーション例外
        if ($e instanceof ValidationException) {
            $message = $defaultMessage ?? __('api.general.validation_error');

            // エラーログを記録
            if ($operation) {
                $this->logError(__("operations.{$operation}"), $e, $request, $additionalContext);
            } else {
                $this->logError(__('operations.general.validation_error'), $e, $request, [
                    'validation_errors' => $e->errors(),
                ]);
            }

            return $this->errorResponse($message, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        // モデル未発見例外
        if ($e instanceof ModelNotFoundException) {
            $message = $defaultMessage ?? __('api.general.not_found');

            // エラーログを記録
            if ($operation) {
                $this->logError(__("operations.{$operation}"), $e, $request, $additionalContext);
            } else {
                $this->logError(__('operations.general.not_found'), $e, $request, [
                    'resource' => 'not_found',
                ]);
            }

            return $this->errorResponse($message, HttpStatusCode::NOT_FOUND);
        }

        // クエリ例外
        if ($e instanceof QueryException) {
            $message = $defaultMessage ?? __('api.general.database_error');

            // エラーログを記録
            if ($operation) {
                $this->logError(__("operations.{$operation}"), $e, $request, $additionalContext);
            } else {
                $this->logError(__('operations.general.database_error'), $e, $request, [
                    'database' => 'operation_failed',
                ]);
            }

            return $this->errorResponse($message, HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        // その他の例外は基本処理
        return $this->handleGenericException($e, $request, $defaultMessage, $operation, $additionalContext);
    }

    /**
     * 汎用例外の処理
     */
    private function handleGenericException(
        Exception $e,
        Request $request,
        string $defaultMessage,
        ?string $operation,
        array $additionalContext
    ): JsonResponse {
        $message = $defaultMessage ?? $e->getMessage();

        $statusCode = method_exists($e, 'getStatusCode') ? HttpStatusCode::from($e->getStatusCode()) : HttpStatusCode::INTERNAL_SERVER_ERROR;

        // エラーログを記録
        if ($operation) {
            $this->logError(__("operations.{$operation}"), $e, $request, $additionalContext);
        } else {
            $this->logError(__('operations.general.exception_handling'), $e, $request, [
                'default_message' => $defaultMessage,
                'status_code' => $statusCode->value
            ]);
        }

        return $this->errorResponse($message, $statusCode);
    }
}
