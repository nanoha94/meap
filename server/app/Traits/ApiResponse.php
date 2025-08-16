<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Traits\LoggingTrait;

trait ApiResponse
{
    use LoggingTrait;
    /**
     * 成功レスポンスを返す
     */
    protected function successResponse(mixed $data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 警告メッセージを含む成功レスポンスを返す
     */
    protected function successResponseWithWarning(mixed $data = null, string $message = '', string $warning = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($warning) {
            $response['warning'] = $warning;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * エラーレスポンスを返す
     */
    protected function errorResponse(string $message, int $statusCode = 400, mixed $errors = null, string $errorType = ''): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_type' => $errorType,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * バリデーションエラーレスポンスを返す
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return $this->errorResponse(
            __('api.general.validation_error'),
            422,
            $exception->errors()
        );
    }

    /**
     * データ作成成功レスポンスを返す
     */
    protected function createdResponse(mixed $data = null, string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.data_created');
        return $this->successResponse($data, $message, 201);
    }

    /**
     * データ更新成功レスポンスを返す
     */
    protected function updatedResponse(mixed $data = null, string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.data_updated');
        return $this->successResponse($data, $message);
    }

    /**
     * データ削除成功レスポンスを返す
     */
    protected function deletedResponse(string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.data_deleted');
        return $this->successResponse(null, $message);
    }

    /**
     * データ一覧取得レスポンスを返す
     */
    protected function indexResponse(mixed $data, ?int $total = null, string $message = ''): JsonResponse
    {
        $response = [
            'data' => $data,
        ];

        if ($total !== null) {
            $response['total'] = $total;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            ...$response
        ], 200);
    }

    /**
     * データ詳細取得レスポンスを返す
     */
    protected function showResponse(mixed $data, string $message = ''): JsonResponse
    {
        return $this->successResponse($data, $message);
    }

    /**
     * 例外をキャッチしてエラーレスポンスを返す
     */
    protected function handleException(\Exception $e, Request $request, string $defaultMessage = null): JsonResponse
    {
        $defaultMessage = $defaultMessage ?? __('api.general.error');
        $message = $e->getMessage() ?: $defaultMessage;
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        // 新しいLoggingTraitを使用してエラーログを記録
        $this->logError(__('operations.general.exception_handling'), $e, $request, [
            'default_message' => $defaultMessage,
            'status_code' => $statusCode
        ]);

        return $this->errorResponse($message, $statusCode);
    }
}
