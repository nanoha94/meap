<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;
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
            'data' => $data,
        ];

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
            'data' => $data,
        ];
        if ($warning) {
            $response['warning'] = $warning;
        }

        return response()->json($response, $statusCode);
    }


    /**
     * データ作成成功レスポンスを返す
     */
    protected function createdResponse(mixed $data = null, ?string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.data_created');
        return $this->successResponse($data, $message, 201);
    }

    /**
     * データ更新成功レスポンスを返す
     */
    protected function updatedResponse(mixed $data = null, ?string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.data_updated');
        return $this->successResponse($data, $message);
    }

    /**
     * データ削除成功レスポンスを返す
     */
    protected function deletedResponse(?string $message = null): JsonResponse
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
     * エラーレスポンスを返す
     */
    protected function errorResponse(string $message, HttpStatusCode | int $statusCode = HttpStatusCode::BAD_REQUEST, mixed $errors = [], ?string $errorType = ''): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_type' => $errorType,
            'error_code' => $statusCode instanceof HttpStatusCode ? $statusCode->value : $statusCode,
            'error_description' => $statusCode instanceof HttpStatusCode ? $statusCode->getDescription() : 'エラーが発生しました',
            'errors' => $errors,
        ];

        return response()->json($response, $statusCode instanceof HttpStatusCode ? $statusCode->value : $statusCode);
    }

    /**
     * データが見つからない場合のエラーレスポンス
     */
    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? __('api.general.not_found');
        return $this->errorResponse($message, HttpStatusCode::NOT_FOUND);
    }

    /**
     * 認証エラーレスポンス
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        $errorMessage = $message ?? __('api.general.unauthorized');
        return $this->errorResponse($errorMessage, HttpStatusCode::UNAUTHORIZED);
    }

    /**
     * 権限エラーレスポンス
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        $errorMessage = $message ?? __('api.general.forbidden');
        return $this->errorResponse($errorMessage, HttpStatusCode::FORBIDDEN);
    }

    /**
     * サーバーエラーレスポンス
     */
    protected function serverErrorResponse(?string $message = null): JsonResponse
    {
        $errorMessage = $message ?? __('api.general.error');
        return $this->errorResponse($errorMessage, HttpStatusCode::INTERNAL_SERVER_ERROR);
    }

    /**
     * データベースエラーレスポンス
     */
    protected function databaseErrorResponse(?string $message = null): JsonResponse
    {
        $errorMessage = $message ?? __('api.general.database_error');
        return $this->errorResponse($errorMessage, HttpStatusCode::INTERNAL_SERVER_ERROR);
    }
}
