<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class ShoppingItemStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|required',
            'categoryId' => 'uuid|required',
            'order' => 'integer|min:0|required',
            'isPinned' => 'boolean|required',
            'isChecked' => 'boolean|required',
            'tags' => 'array|nullable',
            'tags.*.id' => 'uuid|nullable',
            'tags.*.name' => 'string|max:255|required',
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
            'name.string' => __('validation.string', ['attribute' => 'name']),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'categoryId.uuid' => __('validation.uuid', ['attribute' => 'categoryId']),
            'categoryId.required' => __('validation.required', ['attribute' => 'categoryId']),
            'order.integer' => __('validation.integer', ['attribute' => 'order']),
            'order.min' => __('validation.min.numeric', ['attribute' => 'order', 'min' => 0]),
            'order.required' => __('validation.required', ['attribute' => 'order']),
            'isPinned.boolean' => __('validation.boolean', ['attribute' => 'isPinned']),
            'isPinned.required' => __('validation.required', ['attribute' => 'isPinned']),
            'isChecked.boolean' => __('validation.boolean', ['attribute' => 'isChecked']),
            'isChecked.required' => __('validation.required', ['attribute' => 'isChecked']),
            'tags.array' => __('validation.array', ['attribute' => 'tags']),
            'tags.*.id.uuid' => __('validation.uuid', ['attribute' => 'tags.*.id']),
            'tags.*.name.string' => __('validation.string', ['attribute' => 'tags.*.name']),
            'tags.*.name.max' => __('validation.max.string', ['attribute' => 'tags.*.name', 'max' => 255]),
            'tags.*.name.required' => __('validation.required', ['attribute' => 'tags.*.name']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.shopping_item.store');
    }
}
