<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\RecipeCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\HttpStatusCode;
use App\Http\Requests\Api\RecipeCategoryBulkDestroyRequest;
use App\Http\Requests\Api\RecipeCategoryBulkUpdateRequest;
use App\Http\Requests\Api\RecipeCategoryStoreRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    public function store(RecipeCategoryStoreRequest $request): JsonResponse
    {
        try {
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
            return $this->createdResponse($res, __('api.created', ['attribute' => __('api.attributes.recipe_category'), 'name' => $ret->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.creation_failed', ['attribute' => __('api.attributes.recipe_category')]),
                'recipe_category.store'
            );
        }
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
    public function bulkUpdate(RecipeCategoryBulkUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // TODO: バリデーションチェック済みのデータを扱うべきか検討
            $validated = $request->validate();

            // 料理カテゴリーの一括更新
            foreach ($request->data as $category) {
                $ret = RecipeCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    $this->handleException(
                        new HttpException(HttpStatusCode::INTERNAL_SERVER_ERROR->value, __('api.bulk_update_failed', ['attribute' => __('api.attributes.recipe_category')])),
                        $request,
                        __('api.bulk_update_failed', ['attribute' => __('api.attributes.recipe_category')]),
                        __('operations.recipe_category.bulk_update'),
                        ['category_id' => $category['id'], 'category_name' => $category['name']]
                    );
                }
            }

            // 更新後の料理カテゴリーを取得
            $categories = $group->recipeCategories()->where('group_id', $group->id)->select('id', 'name', 'order')->get();

            return $this->updatedResponse($categories, __('api.bulk_updated', ['attribute' => __('api.attributes.recipe_category'), 'count' => $categories->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.bulk_update_failed', ['attribute' => __('api.attributes.recipe_category')]),
                'recipe_category.bulk_update'
            );
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
    public function bulkDestroy(RecipeCategoryBulkDestroyRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;


            // TODO: バリデーションチェック済みのデータを扱うべきか検討
            $validated = $request->validate();

            $deletedIds = [];
            $notFoundIds = [];

            foreach ($request->ids as $id) {
                $category =  RecipeCategory::where('id', $id)->where('group_id', $group->id)->first();

                if (!$category) {
                    $notFoundIds[] = $id;
                    continue;
                }

                $deletedIds[] = $category->id;
                $category->delete();
            }

            // 見つからなかったIDがある場合はエラーレスポンスを返す
            if (!empty($notFoundIds)) {
                $this->handleException(
                    new HttpException(HttpStatusCode::NOT_FOUND->value, __('api.general.not_found')),
                    $request,
                    __('api.general.not_found'),
                    __('operations.recipe_category.bulk_destroy'),
                    ['notFoundIds' => $notFoundIds]
                );
            }

            // 残りのカテゴリーのorderを整理
            $remainingCategories = RecipeCategory::where('group_id', $group->id)
                ->orderBy('order')
                ->get();

            foreach ($remainingCategories as $index => $remainingCategory) {
                $remainingCategory->update(['order' => $index]);
            }

            return $this->deletedResponse(__('api.bulk_deleted', ['attribute' => __('api.attributes.recipe_category'), 'count' => count($deletedIds)]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.deletion_failed', ['attribute' => __('api.attributes.recipe_category')]),
                'recipe_category.bulk_destroy'
            );
        }
    }
}
