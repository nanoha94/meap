<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ValidationLimits;

class RecipeIndexRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'limit' => 'integer|min:1|max:100|nullable',
            'offset' => 'integer|min:0|nullable',
            'sort' => 'nullable|string|in:created_at,last_planned_date,name',
            'order' => 'nullable|string|in:asc,desc',
            'recipe_name' => 'nullable|string|max:' . ValidationLimits::STRING_MAX,
            'ingredient_name' => 'nullable|string|max:' . ValidationLimits::STRING_MAX,
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'uuid|exists:recipe_categories,id',
            'last_planned_date_from' => 'nullable|date|date_format:Y-m-d',
            'last_planned_date_to' => 'nullable|date|date_format:Y-m-d|after_or_equal:last_planned_date_from',
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
            'limit.integer' => __('validation.integer', ['attribute' => 'limit']),
            'limit.min' => __('validation.min.numeric', ['attribute' => 'limit', 'min' => 1]),
            'limit.max' => __('validation.max.numeric', ['attribute' => 'limit', 'max' => 100]),
            'offset.integer' => __('validation.integer', ['attribute' => 'offset']),
            'offset.min' => __('validation.min.numeric', ['attribute' => 'offset', 'min' => 0]),
            'sort.string' => __('validation.string', ['attribute' => 'sort']),
            'sort.in' => __('validation.in', ['attribute' => 'sort']),
            'order.string' => __('validation.string', ['attribute' => 'order']),
            'order.in' => __('validation.in', ['attribute' => 'order']),
            'recipe_name.string' => __('validation.string', ['attribute' => 'recipe_name']),
            'recipe_name.max' => __('validation.max.string', ['attribute' => 'recipe_name', 'max' => 255]),
            'ingredient_name.string' => __('validation.string', ['attribute' => 'ingredient_name']),
            'ingredient_name.max' => __('validation.max.string', ['attribute' => 'ingredient_name', 'max' => 255]),
            'category_ids.array' => __('validation.array', ['attribute' => 'category_ids']),
            'category_ids.*.uuid' => __('validation.uuid', ['attribute' => 'category_ids']),
            'category_ids.*.exists' => __('validation.exists', ['attribute' => 'category_ids']),
            'last_planned_date_from.date' => __('validation.date', ['attribute' => 'last_planned_date_from']),
            'last_planned_date_from.date_format' => __('validation.date_format', ['attribute' => 'last_planned_date_from', 'format' => 'Y-m-d']),
            'last_planned_date_to.date' => __('validation.date', ['attribute' => 'last_planned_date_to']),
            'last_planned_date_to.date_format' => __('validation.date_format', ['attribute' => 'last_planned_date_to', 'format' => 'Y-m-d']),
            'last_planned_date_to.after_or_equal' => __('validation.after_or_equal', ['attribute' => 'last_planned_date_to', 'date' => 'last_planned_date_from']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.recipe.index');
    }
}
