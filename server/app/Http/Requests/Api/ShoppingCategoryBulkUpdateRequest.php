<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ValidationLimits;

class ShoppingCategoryBulkUpdateRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'array|min:1|max:' . ValidationLimits::BULK_CATEGORY_DATA_MAX . '|required',
            'data.*.id' => 'uuid|required',
            'data.*.name' => 'string|max:' . ValidationLimits::STRING_MAX . '|required',
            'data.*.order' => 'integer|min:0|required',
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
            'data.max' => __('validation.max.array', ['attribute' => 'data', 'max' => ValidationLimits::BULK_CATEGORY_DATA_MAX]),
            'data.required' => __('validation.required', ['attribute' => 'data']),
            'data.*.id.uuid' => __('validation.uuid', ['attribute' => 'data.*.id']),
            'data.*.id.required' => __('validation.required', ['attribute' => 'data.*.id']),
            'data.*.name.string' => __('validation.string', ['attribute' => 'data.*.name']),
            'data.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.name', 'max' => 255]),
            'data.*.name.required' => __('validation.required', ['attribute' => 'data.*.name']),
            'data.*.order.integer' => __('validation.integer', ['attribute' => 'data.*.order']),
            'data.*.order.min' => __('validation.min.numeric', ['attribute' => 'data.*.order', 'min' => 0]),
            'data.*.order.required' => __('validation.required', ['attribute' => 'data.*.order']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.shopping_category.bulk_update');
    }
}
