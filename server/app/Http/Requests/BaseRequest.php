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
        // リクエストデータが空で、JSONリクエストの場合、JSONの構文エラーの可能性を示す
        if ($this->isJson() && empty($this->all()) && !empty($this->getContent())) {
            json_decode($this->getContent());
            if (json_last_error() !== JSON_ERROR_NONE) {
                // JSONの構文エラーがある場合、エラーメッセージに追加情報を付与
                $jsonError = json_last_error_msg();
                // 既存のエラーにJSONの構文エラー情報を追加
                $validator->errors()->add('_json_syntax', "JSONの構文エラーが検出されました: {$jsonError}。リクエストボディの形式（特に配列のカンマなど）を確認してください。");
            }
        }

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
