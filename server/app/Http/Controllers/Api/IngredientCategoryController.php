<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\IngredientCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            $this->logError(__('operations.ingredient_category.index'), $e, $request, []);
            return $this->handleException($e, $request, __('api.ingredient.get_failed'));
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
                $this->logError(__('operations.ingredient_category.store'), new Exception(__('api.ingredient.creation_failed')), $request);
                return $this->errorResponse(__('api.ingredient.creation_failed'), 500);
            }

            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'order' => $category->order
            ];

            return $this->createdResponse($data, __('api.ingredient.created', ['name' => $validated['name']]));
        } catch (ValidationException $e) {
            $this->logError(__('operations.ingredient_category.store'), $e, $request);
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError(__('operations.ingredient_category.store'), $e, $request);
            return $this->handleException($e, $request, __('api.ingredient.creation_failed'));
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

            // 更新処理を実行
            foreach ($validated['data'] as $category) {
                $ret = IngredientCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    $this->logWarning(__('operations.ingredient_category.bulk_update'), __('api.ingredient.update_failed'), $request, [
                        'category_id' => $category['id'],
                        'category_name' => $category['name']
                    ]);
                    // 更新に失敗した場合は、そのカテゴリーをスキップして続行
                    continue;
                } else {
                    $updatedCount++;
                    $updatedIds[] = $category['id'];
                }
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
        } catch (ValidationException $e) {
            $this->logError(__('operations.ingredient_category.bulk_update'), $e, $request);
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError(__('operations.ingredient_category.bulk_update'), $e, $request);
            return $this->handleException($e, $request, __('api.ingredient.bulk_update_failed'));
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

            // 削除対象のカテゴリを取得して検証
            $categories = $group->ingredientCategories()
                ->whereIn('id', $validated['ids'])
                ->get();

            // 見つからなかったIDを特定
            $foundIds = $categories->pluck('id')->toArray();
            $notFoundIds = array_diff($validated['ids'], $foundIds);

            if (!empty($notFoundIds)) {
                $this->logWarning(__('operations.ingredient_category.bulk_destroy'), __('api.ingredient.not_found'), $request, [
                    'notFoundIds' => $notFoundIds
                ]);
                return $this->errorResponse(__('api.ingredient.not_found'), 404);
            }

            $deletedCount = $categories->count();
            $group->ingredientCategories()
                ->whereIn('id', $validated['ids'])
                ->delete();

            // 残りのカテゴリーのorderを整理
            $remainingCategories = IngredientCategory::where('group_id', $group->id)
                ->orderBy('order')
                ->get();

            foreach ($remainingCategories as $index => $remainingCategory) {
                $remainingCategory->update(['order' => $index]);
            }

            return $this->deletedResponse(__('api.ingredient.deleted', ['count' => $deletedCount]));
        } catch (ValidationException $e) {
            $this->logError(__('operations.ingredient_category.bulk_destroy'), $e, $request);
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            $this->logError(__('operations.ingredient_category.bulk_destroy'), $e, $request, [
                'notFoundIds' => $notFoundIds ?? []
            ]);
            return $this->handleException($e, $request, __('api.ingredient.deletion_failed'));
        }
    }
}
