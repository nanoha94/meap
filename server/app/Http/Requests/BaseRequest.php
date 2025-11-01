<?php

namespace App\Http\Requests;

use App\Traits\ExceptionHandlerTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

abstract class BaseRequest extends FormRequest
{
    use ExceptionHandlerTrait;

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        // バリデーション失敗時のログ記録とレスポンス生成
        // errors()->first()で最初のエラーメッセージを文字列として取得
        $primaryMessage = $validator->errors()->first() ?? __('api.general.validation_error');

        // primaryMessageが配列の場合は、文字列に変換
        if (is_array($primaryMessage)) {
            $primaryMessage = is_array($primaryMessage[0] ?? null)
                ? (string)($primaryMessage[0][0] ?? __('api.general.validation_error'))
                : (string)($primaryMessage[0] ?? __('api.general.validation_error'));
        }

        // 文字列でない場合はデフォルトメッセージを使用
        if (!is_string($primaryMessage)) {
            $primaryMessage = __('api.general.validation_error');
        }

        // ValidationExceptionを作成
        // toArray()の形式を正規化（ネストされた配列を平坦化）
        $errorsArray = $validator->errors()->toArray();
        $normalizedErrors = [];

        foreach ($errorsArray as $key => $messages) {
            $normalizedMessages = [];
            foreach ($messages as $message) {
                // メッセージが配列（連想配列）の場合はすべての値を取得
                if (is_array($message)) {
                    // 連想配列の場合はすべての値を配列に追加
                    foreach ($message as $msg) {
                        if (is_string($msg) && !in_array($msg, $normalizedMessages, true)) {
                            $normalizedMessages[] = $msg;
                        }
                    }
                } else {
                    // 文字列の場合はそのまま追加
                    $normalizedMessages[] = (string)$message;
                }
            }
            $normalizedErrors[$key] = $normalizedMessages;
        }

        $validationException = ValidationException::withMessages($normalizedErrors);

        // ExceptionHandlerTraitを使用してレスポンスを生成
        $response = $this->handleException(
            $validationException,
            $this,
            $primaryMessage,
            $this->getOperationKey()
        );

        // HttpResponseExceptionでレスポンスを投げる
        throw new HttpResponseException($response);
    }

    /**
     * Get the operation key for error handling.
     * 各Requestクラスでオーバーライドして具体的な操作キーを返す
     *
     * @return string
     */
    abstract protected function getOperationKey(): string;
}
