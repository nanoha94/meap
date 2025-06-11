<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DishCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DishCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/dishes/categories",
     *     summary="料理カテゴリを作成",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/DishCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = DishCategory::create([
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
     *     path="/dishes/categories/{id}",
     *     summary="料理カテゴリを更新",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishCategoryIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/DishCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        DishCategory::where('id', $id)->where('group_id', $group->id)->update([
            'name' => $request->name
        ]);

        $category = $group->dishCategories()->where('id', $id)->select('id', 'name')->first();

        return response()->json($category, 200);
    }

    /**
     * @OA\Delete(
     *     path="/dishes/categories/{id}",
     *     summary="料理カテゴリを削除",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $category =  DishCategory::where('id', $id)->where('group_id', $group->id)->first();

        if (!$category) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $deletedId = $category->id;
        $category->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
