<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MealPlanService extends AbstractDomainService
{
    protected MealCategoryService $mealCategoryService;
    protected RecipeService $recipeService;
    protected ImageService $imageService;

    public function __construct(MealCategoryService $mealCategoryService, RecipeService $recipeService, ImageService $imageService)
    {
        $this->mealCategoryService = $mealCategoryService;
        $this->recipeService = $recipeService;
        $this->imageService = $imageService;
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.meal_plan');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->mealPlans();
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'date', 'meal_category_id'];
    }

    protected function getWithColumns(): array
    {
        return ['mealCategory.color', 'recipes.categories', 'recipes.ingredients'];
    }

    protected function getGroupBy(): string | null
    {
        return 'date';
    }

    protected function formatIndexResponse(Model|Collection $items): array
    {
        // 型チェック
        $this->typeCheck($items, Collection::class);
        $this->typeCheckCollection($items, MealPlan::class);

        return [
            'date' => $items->first()->date,
            'mealPlans' => $items->map(function ($mealPlan) {
                return [
                    'id' => $mealPlan->id,
                    'date' => $mealPlan->date,
                    'category' => $this->formatCategory($mealPlan->mealCategory),
                    'recipes' => $this->formatRecipes($mealPlan->recipes),
                ];
            })
        ];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => $this->formatCategory($item->mealCategory),
            'recipes' => $this->formatRecipes($item->recipes)
        ];
    }

    protected function formatShowResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => $this->formatCategory($item->mealCategory),
            'recipes' => $this->formatRecipes($item->recipes)
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => $this->formatCategory($item->mealCategory),
            'recipes' => $this->formatRecipes($item->recipes)
        ];
    }

    public function create(array $data, Group $group): void
    {
        DB::transaction(function () use ($data, $group) {
            // 献立カテゴリの存在チェック
            $mealCategory = $this->mealCategoryService->findItemsByIds([$data['mealCategoryId']], $group);

            // 献立を作成
            $mealPlan = MealPlan::create([
                'group_id' => $group->id,
                'meal_category_id' => $mealCategory->first()->id,
                'date' => $data['date'],
            ]);

            // 献立・料理を紐づけ
            if (!empty($data['recipeIds'])) {
                $this->syncRecipes($mealPlan, $data['recipeIds'], $group);
            }
        });
    }

    public function update(string $id, array $data, Group $group): void
    {
        DB::transaction(function () use ($id, $data, $group) {
            //更新対象を取得
            $mealPlan = $this->findItemsByIds([$id], $group)->first();

            // 献立カテゴリの存在チェック
            $mealCategory = $this->mealCategoryService->findItemsByIds([$data['mealCategoryId']], $group);

            // 献立を更新
            $mealPlan->update([
                'group_id' => $group->id,
                'meal_category_id' => $mealCategory->first()->id,
                'date' => $data['date'],
            ]);

            // 献立・料理を紐づけ
            if (!empty($data['recipeIds'])) {
                $this->syncRecipes($mealPlan, $data['recipeIds'], $group);
            }
        });
    }

    private function formatCategory($category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'colorCodeHex' => $category->color->color_code_hex,
            'order' => $category->order,
        ];
    }

    /**
     * 献立の料理情報をフォーマット
     */
    private function formatRecipes($recipes): array
    {
        return  $recipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'categories' => $this->recipeService->formatRecipeCategories($recipe->categories),
                'thumbnail' => $this->imageService->formatImage($recipe->thumbnails->first()),
            ];
        })->values()->toArray();
    }

    private function syncRecipes(MealPlan $mealPlan, array $recipeIds, Group $group): void
    {
        // recipeIds をユニークに整形
        $recipeIds = array_values(array_unique($recipeIds));

        if (empty($recipeIds)) {
            $mealPlan->recipes()->sync([]);
            return;
        }

        // レシピの存在チェック（グループに属するもののみ）
        $this->recipeService->findItemsByIds($recipeIds, $group);

        // (meal_plan_id, recipe_id) のみで sync
        $mealPlan->recipes()->sync($recipeIds);
    }
}
