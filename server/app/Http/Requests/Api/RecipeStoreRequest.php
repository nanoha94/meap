<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Models\IngredientUnit;
use Illuminate\Support\Str;

class RecipeStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|required',
            'url' => 'string|max:2048|nullable',
            'thumbnailId' => 'uuid|nullable',
            'categoryIds' => 'array|nullable',
            'categoryIds.*' => 'uuid|required',
            'ingredients' => 'array|nullable',
            'ingredients.*.id' => 'uuid|nullable',
            'ingredients.*.name' => 'string|max:255|required',
            'ingredients.*.unitId' => 'uuid|required',
            'ingredients.*.categoryId' => 'uuid|required',
            'ingredients.*.quantityDisplay' => 'nullable|string|max:50',
            'ingredients.*.order' => 'integer|min:0|nullable',
            'steps' => 'array|nullable',
            'steps.*.id' => 'uuid|nullable',
            'steps.*.instruction' => 'string|max:255|required',
            'steps.*.imageId' => 'uuid|nullable',
            'steps.*.order' => 'integer|min:0|required',
            'memo' => 'string|max:255|nullable',
            'servingCount' => 'integer|min:1|nullable',
            'cookingTime' => 'integer|min:0|nullable',
            'ownerUserId' => 'uuid|required',
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
            'name.string' => __('validation.string', ['attribute' => 'name']),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'url.string' => __('validation.string', ['attribute' => 'url']),
            'url.max' => __('validation.max.string', ['attribute' => 'url', 'max' => 2048]),
            'thumbnailId.uuid' => __('validation.uuid', ['attribute' => 'thumbnailId']),
            'categoryIds.array' => __('validation.array', ['attribute' => 'categoryIds']),
            'categoryIds.*.uuid' => __('validation.uuid', ['attribute' => 'categoryIds.*']),
            'categoryIds.*.required' => __('validation.required', ['attribute' => 'categoryIds.*']),
            'ingredients.array' => __('validation.array', ['attribute' => 'ingredients']),
            'ingredients.*.id.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.id']),
            'ingredients.*.name.string' => __('validation.string', ['attribute' => 'ingredients.*.name']),
            'ingredients.*.name.max' => __('validation.max.string', ['attribute' => 'ingredients.*.name', 'max' => 255]),
            'ingredients.*.name.required' => __('validation.required', ['attribute' => 'ingredients.*.name']),
            'ingredients.*.unitId.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.unitId']),
            'ingredients.*.unitId.required' => __('validation.required', ['attribute' => 'ingredients.*.unitId']),
            'ingredients.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.categoryId']),
            'ingredients.*.categoryId.required' => __('validation.required', ['attribute' => 'ingredients.*.categoryId']),
            'ingredients.*.quantityDisplay.string' => __('validation.string', ['attribute' => 'ingredients.*.quantityDisplay']),
            'ingredients.*.quantityDisplay.max' => __('validation.max.string', ['attribute' => 'ingredients.*.quantityDisplay', 'max' => 50]),
            'ingredients.*.order.integer' => __('validation.integer', ['attribute' => 'ingredients.*.order']),
            'ingredients.*.order.min' => __('validation.min.numeric', ['attribute' => 'ingredients.*.order', 'min' => 0]),
            'steps.array' => __('validation.array', ['attribute' => 'steps']),
            'steps.*.id.uuid' => __('validation.uuid', ['attribute' => 'steps.*.id']),
            'steps.*.instruction.string' => __('validation.string', ['attribute' => 'steps.*.instruction']),
            'steps.*.instruction.max' => __('validation.max.string', ['attribute' => 'steps.*.instruction', 'max' => 255]),
            'steps.*.instruction.required' => __('validation.required', ['attribute' => 'steps.*.instruction']),
            'steps.*.imageId.uuid' => __('validation.uuid', ['attribute' => 'steps.*.imageId']),
            'steps.*.order.integer' => __('validation.integer', ['attribute' => 'steps.*.order']),
            'steps.*.order.min' => __('validation.min.numeric', ['attribute' => 'steps.*.order', 'min' => 0]),
            'steps.*.order.required' => __('validation.required', ['attribute' => 'steps.*.order']),
            'memo.string' => __('validation.string', ['attribute' => 'memo']),
            'memo.max' => __('validation.max.string', ['attribute' => 'memo', 'max' => 255]),
            'servingCount.integer' => __('validation.integer', ['attribute' => 'servingCount']),
            'servingCount.min' => __('validation.min.numeric', ['attribute' => 'servingCount', 'min' => 1]),
            'cookingTime.integer' => __('validation.integer', ['attribute' => 'cookingTime']),
            'cookingTime.min' => __('validation.min.numeric', ['attribute' => 'cookingTime', 'min' => 0]),
            'ownerUserId.uuid' => __('validation.uuid', ['attribute' => 'ownerUserId']),
            'ownerUserId.required' => __('validation.required', ['attribute' => 'ownerUserId']),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ingredients = $this->input('ingredients', []);

            // ingredientsが配列でない場合はスキップ（基本的なバリデーションルールでエラーになる）
            if (!is_array($ingredients)) {
                return;
            }

            foreach ($ingredients as $index => $ingredient) {
                if (!empty($ingredient['unitId']) && Str::isUuid($ingredient['unitId'])) {
                    $quantityDisplay = $ingredient['quantityDisplay'] ?? null;

                    // 配列など型不正は rules() の string ルールに任せる（null は必須チェック対象）
                    if ($quantityDisplay !== null && ! is_string($quantityDisplay)) {
                        continue;
                    }

                    $unit = IngredientUnit::find($ingredient['unitId']);
                    $hasDisplay = is_string($quantityDisplay) && trim($quantityDisplay) !== '';

                    // 数量が必須で、表示表記が未指定の場合はエラー
                    if ($unit && $unit->requires_quantity && ! $hasDisplay) {
                        $validator->errors()->add(
                            "ingredients.{$index}.quantityDisplay",
                            __('validation.required_when_unit_requires_quantity', [
                                'attribute' => 'ingredients.*.quantityDisplay',
                            ])
                        );
                    }
                }
            }

            $seen = [];
            foreach ($ingredients as $index => $ingredient) {
                $key = ($ingredient['name'] ?? '') . '|' . ($ingredient['unitId'] ?? '') . '|' . ($ingredient['categoryId'] ?? '');
                if (in_array($key, $seen, true)) {
                    $validator->errors()->add(
                        "ingredients.{$index}.name",
                        __('validation.duplicate_ingredient')
                    );
                }
                $seen[] = $key;
            }
        });
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.recipe.store');
    }
}
