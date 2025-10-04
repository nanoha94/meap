<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class MealTypeStoreRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'order' => 'required|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.meal_type.name')]),
            'name.string' => __('validation.string', ['attribute' => __('validation.attributes.meal_type.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.meal_type.name'), 'max' => 255]),
            'color.required' => __('validation.required', ['attribute' => __('validation.attributes.meal_type.color')]),
            'color.string' => __('validation.string', ['attribute' => __('validation.attributes.meal_type.color')]),
            'order.required' => __('validation.required', ['attribute' => __('validation.attributes.meal_type.order')]),
            'order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.meal_type.order')]),
            'order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.meal_type.order'), 'min' => 0]),
        ];
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
