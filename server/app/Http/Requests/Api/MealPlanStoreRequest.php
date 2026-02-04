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
            'meals' => 'array|min:1|required',
            'meals.*.id' => 'uuid|nullable',
            'meals.*.categoryId' => 'uuid|required',
            'meals.*.recipeIds' => 'array|min:1|required',
            'meals.*.recipeIds.*' => 'uuid|required',
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
            'meals.array' => __('validation.array', ['attribute' => 'meals']),
            'meals.min' => __('validation.min.array', ['attribute' => 'meals', 'min' => 1]),
            'meals.required' => __('validation.required', ['attribute' => 'meals']),
            'meals.*.id.uuid' => __('validation.uuid', ['attribute' => 'meals.*.id']),
            'meals.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'meals.*.categoryId']),
            'meals.*.categoryId.required' => __('validation.required', ['attribute' => 'meals.*.categoryId']),
            'meals.*.recipeIds.array' => __('validation.array', ['attribute' => 'meals.*.recipeIds']),
            'meals.*.recipeIds.min' => __('validation.min.array', ['attribute' => 'meals.*.recipeIds', 'min' => 1]),
            'meals.*.recipeIds.required' => __('validation.required', ['attribute' => 'meals.*.recipeIds']),
            'meals.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'meals.*.recipeIds.*']),
            'meals.*.recipeIds.*.required' => __('validation.required', ['attribute' => 'meals.*.recipeIds.*']),
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
