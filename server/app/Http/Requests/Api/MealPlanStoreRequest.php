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
            'mealCategoryId' => 'uuid|required',
            'recipeIds' => 'array|min:1|required',
            'recipeIds.*' => 'uuid|required',
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
            'mealCategoryId.uuid' => __('validation.uuid', ['attribute' => 'mealCategoryId']),
            'mealCategoryId.required' => __('validation.required', ['attribute' => 'mealCategoryId']),
            'recipeIds.array' => __('validation.array', ['attribute' => 'recipeIds']),
            'recipeIds.min' => __('validation.min.array', ['attribute' => 'recipeIds', 'min' => 1]),
            'recipeIds.required' => __('validation.required', ['attribute' => 'recipeIds']),
            'recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'recipeIds.*']),
            'recipeIds.*.required' => __('validation.required', ['attribute' => 'recipeIds.*']),
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
