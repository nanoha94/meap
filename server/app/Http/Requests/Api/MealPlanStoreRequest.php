<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class MealPlanStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'date_format:Y-m-d|required',
            'data' => 'array|min:1|required',
            'data.*.id' => 'uuid|nullable',
            'data.*.categoryId' => 'uuid|required',
            'data.*.recipeIds' => 'array|min:1|required',
            'data.*.recipeIds.*' => 'uuid|required',
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
            'date.date_format' => __('validation.date_format', ['attribute' => 'date', 'format' => 'Y-m-d']),
            'date.required' => __('validation.required', ['attribute' => 'date']),
            'data.array' => __('validation.array', ['attribute' => 'data']),
            'data.min' => __('validation.min.array', ['attribute' => 'data', 'min' => 1]),
            'data.required' => __('validation.required', ['attribute' => 'data']),
            'data.*.id.uuid' => __('validation.uuid', ['attribute' => 'data.*.id']),
            'data.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'data.*.categoryId']),
            'data.*.categoryId.required' => __('validation.required', ['attribute' => 'data.*.categoryId']),
            'data.*.recipeIds.array' => __('validation.array', ['attribute' => 'data.*.recipeIds']),
            'data.*.recipeIds.min' => __('validation.min.array', ['attribute' => 'data.*.recipeIds', 'min' => 1]),
            'data.*.recipeIds.required' => __('validation.required', ['attribute' => 'data.*.recipeIds']),
            'data.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'data.*.recipeIds.*']),
            'data.*.recipeIds.*.required' => __('validation.required', ['attribute' => 'data.*.recipeIds.*']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_plan.store');
    }
}
