<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Models\IngredientUnit;
use App\Support\ValidationLimits;
use Illuminate\Support\Str;

class RecipeUpdateRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:' . ValidationLimits::STRING_MAX . '|required',
            'url' => 'nullable|string|url|regex:/^https?:\/\//i|max:2048',
            'thumbnailId' => 'uuid|nullable',
            'categoryIds' => 'array|nullable|max:' . ValidationLimits::RECIPE_CATEGORY_IDS_MAX,
            'categoryIds.*' => 'uuid|required',
            'ingredientCategories' => 'array|nullable|max:' . ValidationLimits::RECIPE_INGREDIENT_CATEGORIES_MAX,
            'ingredientCategories.*.id' => 'uuid|nullable',
            'ingredientCategories.*.name' => 'string|max:' . ValidationLimits::STRING_MAX . '|required',
            'ingredientCategories.*.order' => 'integer|min:0|required',
            'ingredients' => 'array|nullable|max:' . ValidationLimits::RECIPE_INGREDIENTS_MAX,
            'ingredients.*.id' => 'uuid|nullable',
            'ingredients.*.name' => 'string|max:' . ValidationLimits::STRING_MAX . '|nullable',
            'ingredients.*.unitId' => 'uuid|required',
            'ingredients.*.categoryId' => 'uuid|nullable',
            'ingredients.*.categoryName' => 'string|max:' . ValidationLimits::STRING_MAX . '|nullable',
            'ingredients.*.quantityDisplay' => 'nullable|string|max:' . ValidationLimits::STRING_MAX,
            'ingredients.*.order' => 'integer|min:0|nullable',
            'steps' => 'array|nullable|max:' . ValidationLimits::RECIPE_STEPS_MAX,
            'steps.*.id' => 'uuid|nullable',
            'steps.*.instruction' => 'string|max:' . ValidationLimits::STRING_MAX . '|required',
            'steps.*.imageId' => 'uuid|nullable',
            'steps.*.order' => 'integer|min:0|required',
            'memo' => 'string|max:' . ValidationLimits::STRING_MAX . '|nullable',
            'servingCount' => 'integer|min:1|nullable',
            'cookingTime' => 'integer|min:0|nullable',
            'ownerUserId' => 'uuid|required',
            'source' => 'string|in:manual,ai_imported|nullable',
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
            'url.url' => __('validation.url', ['attribute' => 'url']),
            'url.regex' => __('validation.regex', ['attribute' => 'url']),
            'url.max' => __('validation.max.string', ['attribute' => 'url', 'max' => 2048]),
            'thumbnailId.uuid' => __('validation.uuid', ['attribute' => 'thumbnailId']),
            'categoryIds.array' => __('validation.array', ['attribute' => 'categoryIds']),
            'categoryIds.max' => __('validation.max.array', ['attribute' => 'categoryIds', 'max' => ValidationLimits::RECIPE_CATEGORY_IDS_MAX]),
            'categoryIds.*.uuid' => __('validation.uuid', ['attribute' => 'categoryIds.*']),
            'categoryIds.*.required' => __('validation.required', ['attribute' => 'categoryIds.*']),
            'ingredientCategories.array' => __('validation.array', ['attribute' => 'ingredientCategories']),
            'ingredientCategories.max' => __('validation.max.array', ['attribute' => 'ingredientCategories', 'max' => ValidationLimits::RECIPE_INGREDIENT_CATEGORIES_MAX]),
            'ingredientCategories.*.id.uuid' => __('validation.uuid', ['attribute' => 'ingredientCategories.*.id']),
            'ingredientCategories.*.name.string' => __('validation.string', ['attribute' => 'ingredientCategories.*.name']),
            'ingredientCategories.*.name.max' => __('validation.max.string', ['attribute' => 'ingredientCategories.*.name', 'max' => 255]),
            'ingredientCategories.*.name.required' => __('validation.required', ['attribute' => 'ingredientCategories.*.name']),
            'ingredientCategories.*.order.integer' => __('validation.integer', ['attribute' => 'ingredientCategories.*.order']),
            'ingredientCategories.*.order.min' => __('validation.min.numeric', ['attribute' => 'ingredientCategories.*.order', 'min' => 0]),
            'ingredientCategories.*.order.required' => __('validation.required', ['attribute' => 'ingredientCategories.*.order']),
            'ingredients.array' => __('validation.array', ['attribute' => 'ingredients']),
            'ingredients.max' => __('validation.max.array', ['attribute' => 'ingredients', 'max' => ValidationLimits::RECIPE_INGREDIENTS_MAX]),
            'ingredients.*.id.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.id']),
            'ingredients.*.name.string' => __('validation.string', ['attribute' => 'ingredients.*.name']),
            'ingredients.*.name.max' => __('validation.max.string', ['attribute' => 'ingredients.*.name', 'max' => 255]),
            'ingredients.*.name.required' => __('validation.required', ['attribute' => 'ingredients.*.name']),
            'ingredients.*.unitId.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.unitId']),
            'ingredients.*.unitId.required' => __('validation.required', ['attribute' => 'ingredients.*.unitId']),
            'ingredients.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'ingredients.*.categoryId']),
            'ingredients.*.categoryName.string' => __('validation.string', ['attribute' => 'ingredients.*.categoryName']),
            'ingredients.*.categoryName.max' => __('validation.max.string', ['attribute' => 'ingredients.*.categoryName', 'max' => 255]),
            'ingredients.*.quantityDisplay.string' => __('validation.string', ['attribute' => 'ingredients.*.quantityDisplay']),
            'ingredients.*.quantityDisplay.max' => __('validation.max.string', ['attribute' => 'ingredients.*.quantityDisplay', 'max' => ValidationLimits::STRING_MAX]),
            'ingredients.*.order.integer' => __('validation.integer', ['attribute' => 'ingredients.*.order']),
            'ingredients.*.order.min' => __('validation.min.numeric', ['attribute' => 'ingredients.*.order', 'min' => 0]),
            'steps.array' => __('validation.array', ['attribute' => 'steps']),
            'steps.max' => __('validation.max.array', ['attribute' => 'steps', 'max' => ValidationLimits::RECIPE_STEPS_MAX]),
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
            $group = $this->user()?->groups()->first();

            $ownerUserId = $this->input('ownerUserId');
            if (is_string($ownerUserId) && Str::isUuid($ownerUserId)) {
                if ($group && ! $group->users()->where('users.id', $ownerUserId)->exists()) {
                    $validator->errors()->add(
                        'ownerUserId',
                        __('validation.custom.ownerUserId.not_in_group')
                    );
                }
            }

            $ingredients = $this->input('ingredients', []);
            $ingredientCategories = $this->input('ingredientCategories', []);

            if (is_array($ingredientCategories)) {
                $seenCategoryNames = [];
                $seenCategoryIds = [];

                // 食材カテゴリーのバリデーション
                foreach ($ingredientCategories as $index => $category) {
                    $name = $category['name'] ?? '';
                    // nameが指定されていて、seenCategoryNamesに含まれていない場合はエラー
                    if ($name !== '' && in_array($name, $seenCategoryNames, true)) {
                        $field = "ingredientCategories.{$index}.name";
                        $validator->errors()->add(
                            $field,
                            __('validation.duplicate_value', [
                                'attribute' => __('validation.attributes.ingredient_category.name'),
                            ])
                        );
                    }
                    $seenCategoryNames[] = $name;

                    $categoryId = $category['id'] ?? null;
                    // idが指定されていて、seenCategoryIdsに含まれていない場合はエラー
                    if ($categoryId !== null && $categoryId !== '') {
                        if (in_array($categoryId, $seenCategoryIds, true)) {
                            $field = "ingredientCategories.{$index}.id";
                            $validator->errors()->add(
                                $field,
                                __('validation.duplicate_value', [
                                    'attribute' => __('validation.attributes.ingredient_category.id'),
                                ])
                            );
                        }
                        $seenCategoryIds[] = $categoryId;
                    }
                }
            }

            // ingredientsが配列でない場合はスキップ（基本的なバリデーションルールでエラーになる）
            if (! is_array($ingredients)) {
                return;
            }

            $categoryNamesInRequest = is_array($ingredientCategories)
                ? collect($ingredientCategories)->pluck('name')->filter(fn($name) => $name !== '')->all()
                : [];

            // 食材のバリデーション
            $seen = [];
            foreach ($ingredients as $index => $ingredient) {
                $hasId = ! empty($ingredient['id']);
                $hasName = isset($ingredient['name']) && is_string($ingredient['name']) && $ingredient['name'] !== '';
                // idとnameのいずれかが必須
                if (! $hasId && ! $hasName) {
                    $field = "ingredients.{$index}.name";
                    $validator->errors()->add(
                        $field,
                        __('validation.id_or_name_required', [
                            'attribute' => __('validation.attributes.ingredient.name'),
                            'id' => 'id',
                            'name' => 'name',
                        ])
                    );
                }

                if (!empty($ingredient['unitId']) && Str::isUuid($ingredient['unitId'])) {
                    $quantityDisplay = $ingredient['quantityDisplay'] ?? null;

                    // 配列など型不正は rules() の string ルールに任せる（null は必須チェック対象）
                    if ($quantityDisplay === null || is_string($quantityDisplay)) {
                        $unit = $group
                            ? IngredientUnit::query()->where('group_id', $group->id)->find($ingredient['unitId'])
                            : null;
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

                $categoryName = $ingredient['categoryName'] ?? null;
                $hasCategoryName = is_string($categoryName) && $categoryName !== '';

                // categoryNameが指定されていて、categoryNamesInRequestに含まれていない場合はエラー
                if ($hasCategoryName && $categoryNamesInRequest !== [] && ! in_array($categoryName, $categoryNamesInRequest, true)) {
                    $field = "ingredients.{$index}.categoryName";
                    $validator->errors()->add(
                        $field,
                        __('validation.not_in_list', [
                            'attribute' => 'categoryName',
                            'list' => 'ingredientCategories',
                        ])
                    );
                }

                $categoryKey = $ingredient['categoryId'] ?? $ingredient['categoryName'] ?? '';
                $key = ($ingredient['name'] ?? '') . '|' . ($ingredient['unitId'] ?? '') . '|' . $categoryKey;
                // 同じ食材名・単位・カテゴリーの組み合わせが重複している場合はエラー
                if (in_array($key, $seen, true)) {
                    $field = "ingredients.{$index}.name";
                    $validator->errors()->add(
                        $field,
                        __('validation.duplicate_combination', [
                            'fields' => __('validation.attributes.ingredient.combination_fields'),
                        ])
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
        return __('operations.recipe.update');
    }
}
