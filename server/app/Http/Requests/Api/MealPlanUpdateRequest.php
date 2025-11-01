<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class MealPlanUpdateRequest extends BaseApiRequest
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
            'menu' => 'array|min:1|required',
            'menu.*.recipeIds' => 'array|min:1|required',
            'menu.*.recipeIds.*' => 'uuid|required',
            'menu.*.categoryId' => 'uuid|required',
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
            'menu.array' => __('validation.array', ['attribute' => 'menu']),
            'menu.min' => __('validation.min.array', ['attribute' => 'menu', 'min' => 1]),
            'menu.required' => __('validation.required', ['attribute' => 'menu']),
            'menu.*.recipeIds.array' => __('validation.array', ['attribute' => 'menu.*.recipeIds']),
            'menu.*.recipeIds.min' => __('validation.min.array', ['attribute' => 'menu.*.recipeIds', 'min' => 1]),
            'menu.*.recipeIds.required' => __('validation.required', ['attribute' => 'menu.*.recipeIds']),
            'menu.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'menu.*.recipeIds.*']),
            'menu.*.recipeIds.*.required' => __('validation.required', ['attribute' => 'menu.*.recipeIds.*']),
            'menu.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'menu.*.categoryId']),
            'menu.*.categoryId.required' => __('validation.required', ['attribute' => 'menu.*.categoryId']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_plan.update');
    }
}
