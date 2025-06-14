<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\MealCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/meals/categories",
     *     summary="献立カテゴリを作成",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = MealCategory::create([
            'group_id' => $group->id,
            'name' => $request->name,
            'color_id' => $request->colorId,
            'order' => $group->mealCategories->count(),
        ]);

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
            'colorId' => $ret->color_id,
            'order' => $ret->order,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/meals/categories/bulk",
     *     summary="献立カテゴリを更新",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        foreach ($request->categories as $category) {
            $data = MealCategory::find($category['id']);
            if (!$data) {
                continue;
            }

            $data->update([
                'name' => $category['name'],
                'color_id' => $category['colorId'],
                'order' => $data->order
            ]);
        }

        $categories = $group->mealCategories()->select('id', 'name', 'color_id', 'order')->get();
        $ret = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'colorId' => $category->color_id,
                'order' => $category->order
            ];
        });

        return response()->json($ret, 200);
    }

    /**
     * @OA\Delete(
     *     path="/meals/categories/{id}",
     *     summary="献立カテゴリを削除",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $category =  MealCategory::where('id', $id)->where('group_id', $group->id)->first();

        if (!$category) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }
        if ($category->is_default) {
            return response()->json([
                'message' => $category->name . 'は削除できません。'
            ], 403);
        }

        $deletedId = $category->id;
        $category->delete();

        // 残りのカテゴリーのorderを整理
        $remainingCategories = MealCategory::where('group_id', $category->group_id)
            ->orderBy('order')
            ->get();

        foreach ($remainingCategories as $index => $remainingCategory) {
            $remainingCategory->update(['order' => $index]);
        }

        return response()->json(['id' => $deletedId], 200);
    }
}
