<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecipeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'order' => $request->order,
        ]);

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
            'order' => $ret->order,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/recipe-categories/bulk",
     *     summary="料理カテゴリを更新",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $request->validate([
            'data' => 'required|array',
            'data.*.id' => 'required|string',
            'data.*.name' => 'required|string',
            'data.*.order' => 'required|integer',
        ]);

        try {
            // 料理カテゴリーの一括更新
            foreach ($request->data as $category) {
                $ret = RecipeCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    return response()->json([
                        'message' => '料理カテゴリー（' . $category['name'] . '）の更新に失敗しました。'
                    ], 500);
                }
            }

            // 更新後の料理カテゴリーを取得
            $categories = $group->recipeCategories()->where('group_id', $group->id)->select('id', 'name', 'order')->get();

            return response()->json($categories, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '料理カテゴリの一括更新中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/recipe-categories/bulk",
     *     summary="料理カテゴリを削除",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        // 入力値のバリデーション
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|max:255',
        ]);

        $deletedIds = [];
        foreach ($request->ids as $id) {
            $category =  RecipeCategory::where('id', $id)->where('group_id', $group->id)->first();

            if (!$category) {
                Log::error('指定されたレコードが見つかりません。', ['function' => 'ShoppingCategoryController@bulkDestroy', 'id' => $id]);
                return response()->json([
                    'message' => '指定されたレコードが見つかりません。'
                ], 404);
            }

            $deletedIds[] = [$category->id];
            $category->delete();
        }

        // 残りのカテゴリーのorderを整理
        $remainingCategories = RecipeCategory::where('group_id', $category->group_id)
            ->orderBy('order')
            ->get();

        foreach ($remainingCategories as $index => $remainingCategory) {
            $remainingCategory->update(['order' => $index]);
        }

        return response()->json(['ids' => $deletedIds], 200);
    }
}
