<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Api\ApiController;
use App\Models\ShoppingCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingCategoryController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/shopping-categories",
     *     summary="買い物カテゴリ一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $categories = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->orderBy('order', 'asc')->get();
            $formattedData = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'isDefault' => (bool)$category->is_default,
                    'order' => $category->order
                ];
            });

            return $this->indexResponse($formattedData, $formattedData->count(), __('api.shopping.category_list_retrieved', ['count' => $formattedData->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.shopping.category_get_failed'),
                'shopping_category.index',
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/shopping-categories",
     *     summary="買い物カテゴリを作成",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
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

            $category = ShoppingCategory::create([
                'group_id' => $group->id,
                'name' => $validated['name'],
                'is_default' => false,
                'order' => $validated['order'],
            ]);
            if (!$category) {
                $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, __('operations.shopping_category.store'), new Exception(__('api.shopping.category_creation_failed')), $request);
                return $this->errorResponse(__('api.shopping.category_creation_failed'), HttpStatusCode::INTERNAL_SERVER_ERROR);
            }

            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'isDefault' => (bool)$category->is_default,
                'order' => $category->order
            ];

            return $this->createdResponse($data, __('api.shopping.category_created', ['name' => $validated['name']]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.shopping.category_creation_failed'),
                'shopping_category.store'
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/shopping-categories/bulk",
     *     summary="買い物カテゴリを一括更新（isDefaultは更新不可）",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
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
                $ret = ShoppingCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    $this->logWarning(HttpStatusCode::OK, __('operations.shopping_category.bulk_update'), __('api.shopping.category_update_failed'), $request, [
                        'category_id' => $category['id'],
                        'category_name' => $category['name']
                    ],   __METHOD__);
                    // 更新に失敗した場合は、そのカテゴリーをスキップして続行
                    continue;
                } else {
                    $updatedCount++;
                    $updatedIds[] = $category['id'];
                }
            }

            // 更新されたデータを取得
            $updatedCategories = $group->shoppingCategories()
                ->whereIn('id', $updatedIds)
                ->select('id', 'name', 'is_default', 'order')
                ->orderBy('order', 'asc')
                ->get();

            $formattedData = $updatedCategories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'isDefault' => (bool)$category->is_default,
                    'order' => $category->order
                ];
            });

            return $this->updatedResponse($formattedData, __('api.shopping.category_bulk_updated', ['count' => $updatedCount]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.general.bulk_operation_failed'),
                'shopping_category.bulk_update'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/shopping-categories/bulk",
     *     summary="買い物カテゴリを削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
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
            $categories = ShoppingCategory::whereIn('id', $validated['ids'])
                ->where('group_id', $group->id)
                ->get();

            // 見つからなかったIDを特定
            $foundIds = $categories->pluck('id')->toArray();
            $notFoundIds = array_diff($validated['ids'], $foundIds);

            if (!empty($notFoundIds)) {
                $this->logError(HttpStatusCode::NOT_FOUND, __('operations.shopping_category.bulk_destroy'), new Exception(__('api.shopping.not_found')), $request, [
                    'notFoundIds' => $notFoundIds
                ],   __METHOD__);
                return $this->errorResponse(__('api.shopping.not_found'), HttpStatusCode::NOT_FOUND);
            }

            // デフォルトカテゴリのチェック
            $defaultCategory = $categories->where('is_default', true)->first();
            if ($defaultCategory) {
                $this->logWarning(HttpStatusCode::BAD_REQUEST, __('operations.shopping_category.bulk_destroy'), __('api.shopping.default_category_deletion_error', ['name' => $defaultCategory->name]), $request, [
                    'default_category_name' => $defaultCategory->name
                ], __METHOD__);
                return $this->errorResponse(__('api.shopping.default_category_deletion_error', ['name' => $defaultCategory->name]), HttpStatusCode::BAD_REQUEST);
            }

            // 一括削除
            $deletedIds = $categories->pluck('id')->toArray();
            ShoppingCategory::whereIn('id', $validated['ids'])
                ->where('group_id', $group->id)
                ->delete();

            // 残りのカテゴリーのorderを整理
            $remainingCategories = ShoppingCategory::where('group_id', $group->id)
                ->orderBy('order')
                ->get();

            foreach ($remainingCategories as $index => $remainingCategory) {
                $remainingCategory->update(['order' => $index]);
            }

            return $this->deletedResponse(__('api.shopping.category_bulk_deleted', ['count' => count($deletedIds)]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.shopping.category_deletion_failed'),
                'shopping_category.bulk_destroy'
            );
        }
    }
}
