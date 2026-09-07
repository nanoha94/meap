<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ValidationLimits;

class MealCategoryBulkDestroyRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => 'array|min:1|max:' . ValidationLimits::BULK_CATEGORY_DATA_MAX . '|required',
            'ids.*' => 'uuid|required',
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
            'ids.array' => __('validation.array', ['attribute' => 'ids']),
            'ids.min' => __('validation.min.array', ['attribute' => 'ids', 'min' => 1]),
            'ids.max' => __('validation.max.array', ['attribute' => 'ids', 'max' => ValidationLimits::BULK_CATEGORY_DATA_MAX]),
            'ids.required' => __('validation.required', ['attribute' => 'ids']),
            'ids.*.uuid' => __('validation.uuid', ['attribute' => 'ids.*']),
            'ids.*.required' => __('validation.required', ['attribute' => 'ids.*']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_category.bulk_destroy');
    }
}
