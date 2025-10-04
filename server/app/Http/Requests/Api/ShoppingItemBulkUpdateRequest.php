<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ShoppingItemBulkUpdateRequest extends FormRequest
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
            'data' => 'required|array|min:1',
            'data.*.id' => 'required|uuid',
            'data.*.name' => 'required|string|max:255',
            'data.*.categoryId' => 'required|uuid',
            'data.*.isPinned' => 'nullable|boolean',
            'data.*.isChecked' => 'nullable|boolean',
            'data.*.order' => 'required|integer|min:0',
            'data.*.tags' => 'nullable|array',
            'data.*.tags.*.id' => 'nullable|uuid',
            'data.*.tags.*.name' => 'required|string|max:255',
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
            'data.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.data')]),
            'data.array' => __('validation.array', ['attribute' => __('validation.attributes.shopping.item.data')]),
            'data.min' => __('validation.min.array', ['attribute' => __('validation.attributes.shopping.item.data'), 'min' => 1]),
            'data.*.id.required' => __('validation.required', ['attribute' => __('validation.attributes.id')]),
            'data.*.id.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.id')]),
            'data.*.name.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.name')]),
            'data.*.name.string' => __('validation.string', ['attribute' => __('validation.attributes.shopping.item.name')]),
            'data.*.name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.shopping.item.name'), 'max' => 255]),
            'data.*.categoryId.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.category_id')]),
            'data.*.categoryId.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.shopping.item.category_id')]),
            'data.*.isPinned.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.shopping.item.is_pinned')]),
            'data.*.isChecked.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.shopping.item.is_checked')]),
            'data.*.order.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.order')]),
            'data.*.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.shopping.item.order')]),
            'data.*.order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.shopping.item.order'), 'min' => 0]),
            'data.*.tags.array' => __('validation.array', ['attribute' => __('validation.attributes.shopping.item.tags')]),
            'data.*.tags.*.id.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.shopping.item.tag_id')]),
            'data.*.tags.*.name.required' => __('validation.required', ['attribute' => __('validation.attributes.shopping.item.tag_name')]),
            'data.*.tags.*.name.string' => __('validation.string', ['attribute' => __('validation.attributes.shopping.item.tag_name')]),
            'data.*.tags.*.name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.shopping.item.tag_name'), 'max' => 255]),
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
