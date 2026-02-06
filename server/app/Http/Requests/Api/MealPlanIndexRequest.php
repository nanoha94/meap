<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class MealPlanIndexRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => __('validation.required', ['attribute' => 'year']),
            'year.integer' => __('validation.integer', ['attribute' => 'year']),
            'year.min' => __('validation.min.numeric', ['attribute' => 'year', 'min' => 1900]),
            'year.max' => __('validation.max.numeric', ['attribute' => 'year', 'max' => 2100]),
            'month.required' => __('validation.required', ['attribute' => 'month']),
            'month.integer' => __('validation.integer', ['attribute' => 'month']),
            'month.min' => __('validation.min.numeric', ['attribute' => 'month', 'min' => 1]),
            'month.max' => __('validation.max.numeric', ['attribute' => 'month', 'max' => 12]),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_plan.index');
    }
}
