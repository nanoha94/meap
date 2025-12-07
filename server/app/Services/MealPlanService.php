<?php

namespace App\Services;

use App\Models\MenuCategory;
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
    protected MenuCategoryService $menuCategoryService;
    protected ImageService $imageService;

    public function __construct(MealCategoryService $mealCategoryService, RecipeService $recipeService, MenuCategoryService $menuCategoryService, ImageService $imageService)
    {
        $this->mealCategoryService = $mealCategoryService;
        $this->recipeService = $recipeService;
        $this->menuCategoryService = $menuCategoryService;
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
        return ['mealCategory', 'recipes.menuCategories', 'recipes.categories', 'recipes.ingredients'];
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
                    'category' => [
                        'id' => $mealPlan->mealCategory->id,
                        'name' => $mealPlan->mealCategory->name,
                        'colorId' => $mealPlan->mealCategory->color_id,
                    ],
                    'menu' => $this->formatMenu($mealPlan->recipes)
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
            'category' => [
                'id' => $item->mealCategory->id,
                'name' => $item->mealCategory->name,
                'colorId' => $item->mealCategory->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    protected function formatShowResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => [
                'id' => $item->mealCategory->id,
                'name' => $item->mealCategory->name,
                'colorId' => $item->mealCategory->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => [
                'id' => $item->mealCategory->id,
                'name' => $item->mealCategory->name,
                'colorId' => $item->mealCategory->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    public function create(array $data, Group $group): array
    {
        return DB::transaction(function () use ($data, $group) {
            // 献立カテゴリの存在チェック
            $mealCategory = $this->mealCategoryService->findItemsByIds([$data['mealCategoryId']], $group);

            // 献立を作成
            $mealPlan = MealPlan::create([
                'group_id' => $group->id,
                'meal_category_id' => $mealCategory->first()->id,
                'date' => $data['date'],
            ]);

            // 献立・料理・コース種別を紐づけ
            if (!empty($data['menu'])) {
                $this->syncRecipes($mealPlan, $data['menu'], $group);
            }

            return $this->formatStoreResponse($mealPlan);
        });
    }

    public function update(string $id, array $data, Group $group): array
    {
        return DB::transaction(function () use ($id, $data, $group) {
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

            // 献立・料理・コース種別を紐づけ
            if (!empty($data['menu'])) {
                $this->syncRecipes($mealPlan, $data['menu'], $group);
            }

            $item = $mealPlan->fresh(['mealCategory', 'recipes.menuCategories', 'recipes.categories', 'recipes.ingredients']);

            return $this->formatUpdateResponse($item);
        });
    }

    /**
     * 献立のメニュー情報をフォーマット
     */
    private function formatMenu($recipes): array
    {
        return $recipes->groupBy('pivot.menu_category_id')->map(function ($recipes, $menuCategoryId) {
            $menuCategory = MenuCategory::find($menuCategoryId);
            return [
                'category' => [
                    'id' => $menuCategory->id,
                    'name' => $menuCategory->name
                ],
                'recipes' => $recipes->map(fn($recipe) => $this->recipeService->formatIndexResponse($recipe))
            ];
        })->values()->toArray();
    }

    private function syncRecipes(MealPlan $mealPlan, array $menu, Group $group): void
    {
        foreach ($menu as $item) {
            // レシピの存在チェック
            $recipes = $this->recipeService->findItemsByIds($item['recipeIds'], $group);
            // メニューカテゴリの存在チェック
            $menuCategories = $this->menuCategoryService->findItemsByIds([$item['categoryId']], $group);

            // 紐づけ更新
            $attachData = collect($item['recipeIds'])->unique()->map(function ($recipeId) use ($mealPlan, $item) {
                return [
                    'meal_plan_id' => $mealPlan->id,
                    'recipe_id' => $recipeId,
                    'menu_category_id' => $item['categoryId']
                ];
            });
            $mealPlan->recipes()->sync($attachData);
        }
    }
}
