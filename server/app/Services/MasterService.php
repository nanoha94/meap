<?php

namespace App\Services;

use App\Models\Group;

class MasterService
{
    public function __construct(
        private UserService $userService,
        private RecipeCategoryService $recipeCategoryService,
        private IngredientCategoryService $ingredientCategoryService,
        private IngredientUnitService $ingredientUnitService,
        private MenuCategoryService $menuCategoryService,
        private ShoppingCategoryService $shoppingCategoryService,
        private ShoppingTagService $shoppingTagService
    ) {}

    public function index(Group $group): array
    {
        return [
            'users' => $this->userService->index($group),
            'recipeCategories' => $this->recipeCategoryService->index($group),
            'ingredientCategories' => $this->ingredientCategoryService->index($group),
            'ingredientUnits' => $this->ingredientUnitService->index($group),
            'menuCategories' => $this->menuCategoryService->index($group),
            'shoppingCategories' => $this->shoppingCategoryService->index($group),
            'shoppingTags' => $this->shoppingTagService->index($group),
        ];
    }
}
