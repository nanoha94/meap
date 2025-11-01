<?php

namespace App\Http\Controllers;

use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    use ExceptionHandlerTrait;

    /**
     * 例外処理をラップして実行
     * 
     * @param callable $callback 実行する処理
     * @param Request $request リクエストオブジェクト
     * @param string $defaultMessage デフォルトエラーメッセージ
     * @param string $operation 操作名
     * @param array $additionalContext 追加のコンテキスト情報
     * @return JsonResponse
     */
    protected function executeWithExceptionHandling(
        callable $callback,
        Request $request,
        string $defaultMessage,
        string $operation,
        array $additionalContext = []
    ): JsonResponse {
        try {
            return $callback();
        } catch (HttpResponseException $e) {
            // HttpResponseExceptionは既に適切なレスポンスが含まれているので、そのまま再スロー
            throw $e;
        } catch (Exception $e) {
            return $this->handleException($e, $request, $defaultMessage, $operation, $additionalContext);
        }
    }
}
