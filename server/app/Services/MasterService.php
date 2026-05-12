<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class MasterService
{
    public function __construct(
        private UserService $userService,
        private RecipeCategoryService $recipeCategoryService,
        private IngredientCategoryService $ingredientCategoryService,
        private IngredientUnitService $ingredientUnitService,
        private MealCategoryService $mealCategoryService,
        private ShoppingCategoryService $shoppingCategoryService,
        private ShoppingTagService $shoppingTagService
    ) {}

    public function index(Group $group): array
    {
        return Cache::remember(
            "master:{$group->id}",
            now()->addMinutes(30),
            fn (): array => [
                'users' => $this->userService->index($group),
                'recipeCategories' => $this->recipeCategoryService->index($group),
                'ingredientCategories' => $this->ingredientCategoryService->index($group),
                'ingredientUnits' => $this->ingredientUnitService->index($group),
                'mealCategories' => $this->mealCategoryService->index($group),
                'shoppingCategories' => $this->shoppingCategoryService->index($group),
                'shoppingTags' => $this->shoppingTagService->index($group),
            ]
        );
    }

    /**
     * グループのマスターAPIレスポンスキャッシュを破棄する（マスター系データ更新時に呼ぶ）
     */
    public static function forgetGroupCache(Group|int|string $group): void
    {
        $id = $group instanceof Group ? $group->id : $group;
        Cache::forget("master:{$id}");
    }
}
