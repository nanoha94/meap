<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\RecipeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecipeCategoryController extends ApiController
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

        $res = [
            'id' => $ret->id,
            'name' => $ret->name,
            'order' => $ret->order,
        ];
        return $this->createdResponse($res, __('api.recipe.category_created', ['name' => $ret->name]));
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
                    return $this->errorResponse(__('api.recipe.category_update_failed'), 500);
                }
            }

            // 更新後の料理カテゴリーを取得
            $categories = $group->recipeCategories()->where('group_id', $group->id)->select('id', 'name', 'order')->get();

            return $this->updatedResponse($categories, __('api.recipe.category_updated', ['count' => $categories->count()]));
        } catch (\Exception $e) {
            return $this->handleException($e, $request, __('api.recipe.category_bulk_update_failed'));
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
                return $this->notFoundResponse(__('api.general.not_found'));
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

        return $this->deletedResponse(__('api.recipe.category_deleted', ['count' => count($deletedIds)]));
    }
}
