<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait ApiResponse
{
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
     * エラーレスポンスを返す
     */
    protected function errorResponse(string $message, int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
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
            'バリデーションエラーが発生しました。',
            422,
            $exception->errors()
        );
    }

    /**
     * データ作成成功レスポンスを返す
     */
    protected function createdResponse(mixed $data = null, string $message = '作成が完了しました。'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * データ更新成功レスポンスを返す
     */
    protected function updatedResponse(mixed $data = null, string $message = '更新が完了しました。'): JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * データ削除成功レスポンスを返す
     */
    protected function deletedResponse(string $message = '削除が完了しました。'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
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
        return $this->successResponse($data, $message, 200);
    }

    /**
     * 例外をキャッチしてエラーレスポンスを返す
     */
    protected function handleException(\Exception $e, Request $request, string $defaultMessage = 'エラーが発生しました。'): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;
        $message = $e->getMessage() ?: $defaultMessage;
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        Log::error('Exception:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'function' => $e->getFile() . ':' . $e->getLine(),
            'group_id' => $group->id ?? null,
            'user_id' => $user->id ?? null,
        ]);

        return $this->errorResponse($message, $statusCode);
    }
}
