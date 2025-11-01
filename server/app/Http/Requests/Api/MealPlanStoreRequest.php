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
            'mealTypeId' => 'uuid|required',
            'menu' => 'array|min:1|required',
            'menu.*.recipeIds' => 'array|min:1|required',
            'menu.*.recipeIds.*' => 'uuid|required',
            'menu.*.courseTypeId' => 'uuid|required',
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
            'mealTypeId.uuid' => __('validation.uuid', ['attribute' => 'mealTypeId']),
            'mealTypeId.required' => __('validation.required', ['attribute' => 'mealTypeId']),
            'menu.array' => __('validation.array', ['attribute' => 'menu']),
            'menu.min' => __('validation.min.array', ['attribute' => 'menu', 'min' => 1]),
            'menu.required' => __('validation.required', ['attribute' => 'menu']),
            'menu.*.recipeIds.array' => __('validation.array', ['attribute' => 'menu.*.recipeIds']),
            'menu.*.recipeIds.min' => __('validation.min.array', ['attribute' => 'menu.*.recipeIds', 'min' => 1]),
            'menu.*.recipeIds.required' => __('validation.required', ['attribute' => 'menu.*.recipeIds']),
            'menu.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'menu.*.recipeIds.*']),
            'menu.*.recipeIds.*.required' => __('validation.required', ['attribute' => 'menu.*.recipeIds.*']),
            'menu.*.courseTypeId.uuid' => __('validation.uuid', ['attribute' => 'menu.*.courseTypeId']),
            'menu.*.courseTypeId.required' => __('validation.required', ['attribute' => 'menu.*.courseTypeId']),
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
