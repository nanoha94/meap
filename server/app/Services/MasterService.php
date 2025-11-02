<?php

namespace App\Services;

use App\Models\Group;

class MasterService
{
    public function index(Group $group): array
    {
        $recipeCategories = $group->recipeCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
        $ingredientCategories = $group->ingredientCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
        $ingredientUnits = $group->ingredientUnits()->select('id', 'name', 'position', 'requires_quantity', 'order')->orderBy('order', 'asc')->get();
        $menuCategories = $group->menuCategories()->select('id', 'name', 'order')->get();
        $shopping_tags = $group->shoppingTags()->select('id', 'name')->get();

        return [
            'recipeCategories' => $recipeCategories,
            'ingredientCategories' => $ingredientCategories,
            'ingredientUnits' => $ingredientUnits,
            'menuCategories' => $menuCategories,
            'shoppingTags' => $shopping_tags,
        ];
    }
}
