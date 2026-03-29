<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class ShoppingItemBulkStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'array|min:1|required',
            'data.*.name' => 'string|max:255|required',
            'data.*.categoryId' => 'uuid|required',
            'data.*.tags' => 'array|nullable',
            'data.*.tags.*.id' => 'uuid|nullable',
            'data.*.tags.*.name' => 'string|max:255|required',
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
            'data.array' => __('validation.array', ['attribute' => 'data']),
            'data.min' => __('validation.min.array', ['attribute' => 'data', 'min' => 1]),
            'data.required' => __('validation.required', ['attribute' => 'data']),
            'data.*.name.string' => __('validation.string', ['attribute' => 'data.*.name']),
            'data.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.name', 'max' => 255]),
            'data.*.name.required' => __('validation.required', ['attribute' => 'data.*.name']),
            'data.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'data.*.categoryId']),
            'data.*.categoryId.required' => __('validation.required', ['attribute' => 'data.*.categoryId']),
            'data.*.tags.array' => __('validation.array', ['attribute' => 'data.*.tags']),
            'data.*.tags.*.id.uuid' => __('validation.uuid', ['attribute' => 'data.*.tags.*.id']),
            'data.*.tags.*.name.string' => __('validation.string', ['attribute' => 'data.*.tags.*.name']),
            'data.*.tags.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.tags.*.name', 'max' => 255]),
            'data.*.tags.*.name.required' => __('validation.required', ['attribute' => 'data.*.tags.*.name']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.shopping_item.bulk_store');
    }
}
