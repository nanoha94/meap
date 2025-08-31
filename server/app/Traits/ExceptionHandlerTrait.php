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
        string $defaultMessage,
        string $operation,
        array $additionalContext = []
    ): JsonResponse {
        // バリデーション例外
        if ($e instanceof ValidationException) {
            $message = $defaultMessage ?? __('api.general.validation_error');
            $this->logError(HttpStatusCode::UNPROCESSABLE_ENTITY, $operation, $e, $request, array_merge($additionalContext, [
                'validation_errors' => $e->errors(),
                'message' => $message,
            ]));
            return $this->errorResponse($message, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        // モデル未発見例外
        if ($e instanceof ModelNotFoundException) {
            $message = $defaultMessage ?? __('api.general.not_found');
            $this->logError(HttpStatusCode::NOT_FOUND, $operation, $e, $request, array_merge($additionalContext, [
                'search_conditions' => $e->getIds(),
                'message' => $message,
            ]));
            return $this->errorResponse($message, HttpStatusCode::NOT_FOUND);
        }

        // クエリ例外
        if ($e instanceof QueryException) {
            $message = $defaultMessage ?? __('api.general.database_error');
            $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, $operation, $e, $request, array_merge($additionalContext, [
                'message' => $message,
            ]));
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
        string $operation,
        array $additionalContext
    ): JsonResponse {
        $message = $defaultMessage ?? $e->getMessage();
        $statusCode = $this->getExceptionStatusCode($e);

        $this->logError(HttpStatusCode::from($statusCode), $operation, $e, $request, array_merge($additionalContext, [
            'message' => $message,
        ]));

        return $this->errorResponse($message, HttpStatusCode::from($statusCode));
    }

    /**
     * 例外のステータスコードを取得
     */
    private function getExceptionStatusCode(Exception $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            try {
                return $e->getStatusCode();
            } catch (\ValueError $e) {
                // 無効なステータスコードの場合は500を返す
                return 500;
            }
        }
        return 500;
    }
}
