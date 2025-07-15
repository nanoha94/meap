<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecipeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/recipe-categories",
     *     summary="料理カテゴリを作成",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = RecipeCategory::create([
            'group_id' => $group->id,
            'name' => $request->name,
        ]);

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/recipe-categories/{id}",
     *     summary="料理カテゴリを更新",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeCategoryIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        RecipeCategory::where('id', $id)->where('group_id', $group->id)->update([
            'name' => $request->name
        ]);

        $category = $group->recipeCategories()->where('id', $id)->select('id', 'name')->first();

        return response()->json($category, 200);
    }

    /**
     * @OA\Delete(
     *     path="/recipe-categories/{id}",
     *     summary="料理カテゴリを削除",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $type =  RecipeCategory::where('id', $id)->where('group_id', $group->id)->first();

        if (!$type) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $deletedId = $type->id;
        $type->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
