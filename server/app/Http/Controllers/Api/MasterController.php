<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IngredientUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/master",
     *     summary="マスターデータを取得",
     *     tags={"Master"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MasterSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $recipeCategories = $group->recipeCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
        $ingredientCategories = $group->ingredientCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
        $ingredientUnits = $group->ingredientUnits()->select('id', 'name', 'position', 'requires_quantity', 'order')->orderBy('order', 'asc')->get();
        $courseTypes = $group->courseTypes()->select('id', 'name', 'order')->get();
        $shopping_ategories = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->orderBy('order', 'asc')->get();
        $shopping_tags = $group->shoppingTags()->select('id', 'name')->get();
        $res = [
            'recipeCategories' =>  $recipeCategories,
            'ingredientCategories' => $ingredientCategories,
            'ingredientUnits' => $ingredientUnits,
            'courseTypes' => $courseTypes,
            'shoppingCategories' => $shopping_ategories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'isDefault' => (bool)$category->is_default,
                    'order' => $category->order
                ];
            }),
            'shoppingTags' => $shopping_tags,
        ];

        return response()->json($res, 200);
    }
}
