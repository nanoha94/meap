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
            'meals.*.order' => 'integer|min:0|required',
            'meals.*.recipes' => 'array|min:1|required',
            'meals.*.recipes.*.id' => 'uuid|required',
            'meals.*.recipes.*.order' => 'integer|min:0|required',
        ];
    }

    /**
     * Configure the validator (distinct per meal: 同一 meal 内の recipes.*.id の重複チェック).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $meals = $this->input('meals', []);
            if (! is_array($meals)) {
                return;
            }
            foreach ($meals as $mealIndex => $meal) {
                $recipes = $meal['recipes'] ?? [];
                if (! is_array($recipes)) {
                    continue;
                }
                $seen = [];
                foreach ($recipes as $idx => $item) {
                    $id = is_array($item) ? ($item['id'] ?? null) : null;
                    if ($id === null) {
                        continue;
                    }
                    if (isset($seen[$id])) {
                        $validator->errors()->add(
                            "meals.{$mealIndex}.recipes.{$idx}.id",
                            __('validation.distinct', ['attribute' => 'meals.*.recipes.*.id'])
                        );
                    } else {
                        $seen[$id] = $idx;
                    }
                }
            }
        });
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
            'meals.*.order.integer' => __('validation.integer', ['attribute' => 'meals.*.order']),
            'meals.*.order.min' => __('validation.min.numeric', ['attribute' => 'meals.*.order', 'min' => 0]),
            'meals.*.order.required' => __('validation.required', ['attribute' => 'meals.*.order']),
            'meals.*.recipes.array' => __('validation.array', ['attribute' => 'meals.*.recipes']),
            'meals.*.recipes.min' => __('validation.min.array', ['attribute' => 'meals.*.recipes', 'min' => 1]),
            'meals.*.recipes.required' => __('validation.required', ['attribute' => 'meals.*.recipes']),
            'meals.*.recipes.*.id.uuid' => __('validation.uuid', ['attribute' => 'meals.*.recipes.*.id']),
            'meals.*.recipes.*.id.required' => __('validation.required', ['attribute' => 'meals.*.recipes.*.id']),
            'meals.*.recipes.*.order.integer' => __('validation.integer', ['attribute' => 'meals.*.recipes.*.order']),
            'meals.*.recipes.*.order.min' => __('validation.min.numeric', ['attribute' => 'meals.*.recipes.*.order', 'min' => 0]),
            'meals.*.recipes.*.order.required' => __('validation.required', ['attribute' => 'meals.*.recipes.*.order']),
            'meals.*.recipes.*.id.distinct' => __('validation.distinct', ['attribute' => 'meals.*.recipes.*.id']),
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
