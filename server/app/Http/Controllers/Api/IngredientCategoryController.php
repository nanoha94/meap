<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\IngredientCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\HttpStatusCode;

class IngredientCategoryController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/ingredient-categories",
     *     summary="食材カテゴリ一覧を取得",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $categories = $group->ingredientCategories()
                ->select('id', 'name', 'order')
                ->orderBy('order', 'asc')
                ->get();

            $formattedData = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'order' => $category->order
                ];
            });

            return $this->indexResponse($formattedData, $formattedData->count(), __('api.ingredient.list_retrieved', ['count' => $formattedData->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.ingredient.get_failed'),
                'ingredient_category.index'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/ingredient-categories",
     *     summary="食材カテゴリを作成",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryStoreRequest"),
     *     @OA\Response(response=201, ref="#/components/responses/IngredientCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'order' => 'required|integer|min:0',
            ]);

            $category = IngredientCategory::create([
                'group_id' => $group->id,
                'name' => $validated['name'],
                'order' => $validated['order'],
            ]);
            if (!$category) {
                $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, __('operations.ingredient_category.store'), new Exception(__('api.ingredient.creation_failed')), $request);
                return $this->errorResponse(__('api.ingredient.creation_failed'), HttpStatusCode::INTERNAL_SERVER_ERROR);
            }

            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'order' => $category->order
            ];

            return $this->createdResponse($data, __('api.ingredient.created', ['name' => $validated['name']]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.ingredient.creation_failed'),
                'ingredient_category.store'
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/ingredient-categories/bulk",
     *     summary="食材カテゴリを一括更新",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $validated = $request->validate([
                'data' => 'required|array',
                'data.*.id' => 'required|string|max:255',
                'data.*.name' => 'required|string|max:255',
                'data.*.order' => 'required|integer|min:0',
            ]);

            $updatedCount = 0;
            $updatedIds = [];
            $failedCount = 0;
            $failedIds = [];

            // 更新処理を実行
            foreach ($validated['data'] as $category) {
                $ret = IngredientCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    $failedCount++;
                    $failedIds[] = $category['id'];
                    continue;
                } else {
                    $updatedCount++;
                    $updatedIds[] = $category['id'];
                }
            }

            if ($failedCount > 0) {
                $this->logWarning(HttpStatusCode::OK, __('operations.ingredient_category.bulk_update'), __('api.ingredient.bulk_update_partial_success'), $request, [
                    'total_count' => count($validated['data']),
                    'success_count' => $updatedCount,
                    'failed_count' => $failedCount,
                    'failed_ids' => $failedIds,
                ]);
            }

            // 更新されたデータを取得
            $updatedCategories = $group->ingredientCategories()
                ->whereIn('id', $updatedIds)
                ->select('id', 'name', 'order')
                ->orderBy('order', 'asc')
                ->get();

            $formattedData = $updatedCategories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'order' => $category->order
                ];
            });

            return $this->updatedResponse($formattedData, __('api.ingredient.updated', ['count' => $updatedCount]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.ingredient.bulk_update_failed'),
                'ingredient_category.bulk_update'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/ingredient-categories/bulk",
     *     summary="食材カテゴリを削除",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'string',
            ]);

            $deletedCount = 0;
            $deletedIds = [];
            $failedCount = 0;
            $failedIds = [];

            // 削除処理を実行
            foreach ($validated['ids'] as $id) {
                $category = $group->ingredientCategories()->where('id', $id)->first();

                if (!$category) {
                    $failedCount++;
                    $failedIds[] = $id;
                    continue;
                }

                if ($category->delete()) {
                    $deletedCount++;
                    $deletedIds[] = $id;
                } else {
                    $failedCount++;
                    $failedIds[] = $id;
                }
            }

            // 部分成功のログ
            if ($failedCount > 0) {
                $this->logWarning(HttpStatusCode::OK, __('operations.ingredient_category.bulk_destroy'), __('api.ingredient.bulk_destroy_partial_success'), $request, [
                    'total_count' => count($validated['ids']),
                    'success_count' => $deletedCount,
                    'failed_count' => $failedCount,
                    'failed_ids' => $failedIds,
                ]);
            }

            // 残りのカテゴリーのorderを整理
            if ($deletedCount > 0) {
                $remainingCategories = IngredientCategory::where('group_id', $group->id)
                    ->orderBy('order')
                    ->get();

                foreach ($remainingCategories as $index => $remainingCategory) {
                    $remainingCategory->update(['order' => $index]);
                }
            }

            return $this->deletedResponse(__('api.ingredient.deleted', ['count' => $deletedCount]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.ingredient.deletion_failed'),
                'ingredient_category.bulk_destroy'
            );
        }
    }
}
