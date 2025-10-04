<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ImageBulkUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'images' => 'required|array|min:1|max:20',
            'images.0' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'directory' => 'nullable|string|max:255',
        ];

        // 2枚目から20枚目までを任意フィールドとして追加
        for ($i = 1; $i < 20; $i++) {
            $rules["images.{$i}"] = 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        $messages = [
            'images.required' => __('validation.required', ['attribute' => __('validation.attributes.image.files')]),
            'images.array' => __('validation.array', ['attribute' => __('validation.attributes.image.files')]),
            'images.min' => __('validation.min.array', ['attribute' => __('validation.attributes.image.files'), 'min' => 1]),
            'images.max' => __('validation.max.array', ['attribute' => __('validation.attributes.image.files'), 'max' => 20]),
            'images.0.required' => __('validation.required', ['attribute' => __('validation.attributes.image.files')]),
            'images.0.file' => __('validation.file', ['attribute' => __('validation.attributes.image.files')]),
            'images.0.image' => __('validation.image', ['attribute' => __('validation.attributes.image.files')]),
            'images.0.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.image.files'), 'values' => 'png,jpeg,jpg,gif,webp']),
            'images.0.max' => __('validation.max.file', ['attribute' => __('validation.attributes.image.files'), 'max' => 10240]),
            'directory.string' => __('validation.string', ['attribute' => __('validation.attributes.image.directory')]),
            'directory.max' => __('validation.max.string', ['attribute' => __('validation.attributes.image.directory'), 'max' => 255]),
        ];

        // 2枚目以降のエラーメッセージを追加
        for ($i = 1; $i < 20; $i++) {
            $messages["images.{$i}.file"] = __('validation.file', ['attribute' => __('validation.attributes.image.files')]);
            $messages["images.{$i}.image"] = __('validation.image', ['attribute' => __('validation.attributes.image.files')]);
            $messages["images.{$i}.mimes"] = __('validation.mimes', ['attribute' => __('validation.attributes.image.files'), 'values' => 'png,jpeg,jpg,gif,webp']);
            $messages["images.{$i}.max"] = __('validation.max.file', ['attribute' => __('validation.attributes.image.files'), 'max' => 10240]);
        }

        return $messages;
    }

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
        $errorMessages = $validator->errors()->all();
        $primaryMessage = !empty($errorMessages) ? $errorMessages[0] : __('api.general.validation_error');

        // ValidationExceptionを作成
        $validationException = ValidationException::withMessages($validator->errors()->toArray());

        // ExceptionHandlerTraitを使用してレスポンスを生成
        $response = $this->handleException(
            $validationException,
            $this,
            $primaryMessage,
            __('operations.auth.password_reset')
        );

        // HttpResponseExceptionでレスポンスを投げる
        throw new HttpResponseException($response);
    }
}
