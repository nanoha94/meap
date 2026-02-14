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
            'meals' => 'array|min:1|required',
            'meals.*.id' => 'uuid|nullable',
            'meals.*.categoryId' => 'uuid|required',
            'meals.*.order' => 'integer|min:0|required',
            'meals.*.recipeIds' => 'array|min:1|required',
            'meals.*.recipeIds.*' => 'uuid|required',
        ];
    }

    /**
     * Configure the validator (distinct per meal: 同一 meal 内の recipeIds のみ重複チェック).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $meals = $this->input('meals', []);
            if (! is_array($meals)) {
                return;
            }
            foreach ($meals as $mealIndex => $meal) {
                $recipeIds = $meal['recipeIds'] ?? [];
                if (! is_array($recipeIds)) {
                    continue;
                }
                $seen = [];
                foreach ($recipeIds as $idx => $id) {
                    if (isset($seen[$id])) {
                        $validator->errors()->add(
                            "meals.{$mealIndex}.recipeIds.{$idx}",
                            __('validation.distinct', ['attribute' => 'meals.*.recipeIds.*'])
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
            'meals.array' => __('validation.array', ['attribute' => 'meals']),
            'meals.min' => __('validation.min.array', ['attribute' => 'meals', 'min' => 1]),
            'meals.required' => __('validation.required', ['attribute' => 'meals']),
            'meals.*.id.uuid' => __('validation.uuid', ['attribute' => 'meals.*.id']),
            'meals.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'meals.*.categoryId']),
            'meals.*.categoryId.required' => __('validation.required', ['attribute' => 'meals.*.categoryId']),
            'meals.*.order.integer' => __('validation.integer', ['attribute' => 'meals.*.order']),
            'meals.*.order.min' => __('validation.min.numeric', ['attribute' => 'meals.*.order', 'min' => 0]),
            'meals.*.order.required' => __('validation.required', ['attribute' => 'meals.*.order']),
            'meals.*.recipeIds.array' => __('validation.array', ['attribute' => 'meals.*.recipeIds']),
            'meals.*.recipeIds.min' => __('validation.min.array', ['attribute' => 'meals.*.recipeIds', 'min' => 1]),
            'meals.*.recipeIds.required' => __('validation.required', ['attribute' => 'meals.*.recipeIds']),
            'meals.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => 'meals.*.recipeIds.*']),
            'meals.*.recipeIds.*.required' => __('validation.required', ['attribute' => 'meals.*.recipeIds.*']),
            'meals.*.recipeIds.*.distinct' => __('validation.distinct', ['attribute' => 'meals.*.recipeIds.*']),
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
