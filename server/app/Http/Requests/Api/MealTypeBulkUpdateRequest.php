<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class MealTypeBulkUpdateRequest extends BaseApiRequest
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
            'data.*.id' => 'uuid|required',
            'data.*.name' => 'string|max:255|required',
            'data.*.colorId' => 'uuid|exists:colors,id|required',
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
            'data.required' => __('validation.required', ['attribute' => 'data']),
            'data.*.id.uuid' => __('validation.uuid', ['attribute' => 'data.*.id']),
            'data.*.id.required' => __('validation.required', ['attribute' => 'data.*.id']),
            'data.*.name.string' => __('validation.string', ['attribute' => 'data.*.name']),
            'data.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.name', 'max' => 255]),
            'data.*.name.required' => __('validation.required', ['attribute' => 'data.*.name']),
            'data.*.colorId.uuid' => __('validation.uuid', ['attribute' => 'data.*.colorId']),
            'data.*.colorId.exists' => __('validation.exists', ['attribute' => 'data.*.colorId']),
            'data.*.colorId.required' => __('validation.required', ['attribute' => 'data.*.colorId']),
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
        return __('operations.meal_type.bulk_update');
    }
}
