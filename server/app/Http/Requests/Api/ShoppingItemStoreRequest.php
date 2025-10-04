<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ShoppingItemStoreRequest extends FormRequest
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
            'categoryId' => 'required|uuid',
            'tags' => 'nullable|array',
            'tags.*.id' => 'nullable|uuid',
            'tags.*.name' => 'required|string|max:255',
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.name')]),
            'name.string' => __('validation.string', ['attribute' => __('validation.attributes.shopping.item.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.shopping.item.name'), 'max' => 255]),
            'categoryId.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.category_id')]),
            'categoryId.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.shopping.item.category_id')]),
            'tags.array' => __('validation.array', ['attribute' => __('validation.attributes.shopping.item.tags')]),
            'tags.*.id.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.shopping.item.tag_id')]),
            'tags.*.name.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.tag_name')]),
            'tags.*.name.string' => __('validation.string', ['attribute' => __('validation.attributes.shopping.item.tag_name')]),
            'tags.*.name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.shopping.item.tag_name'), 'max' => 255]),
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
            __('operations.shopping_item.store')
        );

        // HttpResponseExceptionでレスポンスを投げる
        throw new HttpResponseException($response);
    }
}
