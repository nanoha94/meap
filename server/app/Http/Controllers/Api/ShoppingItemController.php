<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\ShoppingItem;
use App\Models\ShoppingTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShoppingItemController extends ApiController
{
    use AutoComplement;

    /**
     * @OA\Get(
     *     path="/shopping-items",
     *     summary="買い物アイテム一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $res = [];
            $categories = $group->shoppingCategories()
                ->select('id', 'name', 'is_default', 'order')
                ->orderBy('order', 'asc')
                ->get();

            foreach ($categories as $category) {
                $items = $category->shoppingItems()
                    ->select('id', 'name', 'is_pinned', 'is_checked', 'category_id', 'order')
                    ->with('tags:id,name')
                    ->orderBy('order', 'asc')
                    ->get();

                $res[] = [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'isDefault' => (bool)$category->is_default,
                        'order' => $category->order
                    ],
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'isPinned' => (bool)$item->is_pinned,
                            'isChecked' => (bool)$item->is_checked,
                            'categoryId' => $item->category_id,
                            'tags' => $item->tags->map(function ($tag) {
                                return [
                                    'id' => $tag->id,
                                    'name' => $tag->name
                                ];
                            }),
                            'order' => $item->order
                        ];
                    })
                ];
            }

            return $this->indexResponse($res, $categories->count(), __('api.shopping.list_retrieved', ['count' => $categories->count()]));
        } catch (Exception $e) {
            $this->logError(__('operations.shopping_item.index'), $e, $request);
            return $this->handleException($e, $request, __('api.shopping.list_get_failed'));
        }
    }

    /**
     * @OA\Post(
     *     path="/shopping-items",
     *     summary="買い物アイテムを作成",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemStoreSuccess"),
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
            $request->validate([
                'name' => 'required|string|max:255',
                'categoryId' => 'required|string|max:255',
                'tags' => 'nullable|array',
                'tags.*.id' => 'nullable|string|max:255',
                'tags.*.name' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            $ret = ShoppingItem::create([
                'group_id' => $group->id,
                'category_id' => $request->categoryId,
                'name' => $request->name,
                'is_pinned' => false,
                'is_checked' => false,
                'order' => $group->shoppingItems->where('category_id', $request->categoryId)->count()
            ]);
            if (!$ret) {
                DB::rollBack();
                $this->logError(__('operations.shopping_item.store'), new Exception(__('api.shopping.creation_failed')), $request);
                return $this->errorResponse(__('api.shopping.creation_failed'), 500);
            }

            // タグの処理
            if (!empty($request->tags)) {
                try {
                    $tagIds = $this->findOrCreateIds($request->tags, $group, ShoppingTag::class);
                    if (empty($tagIds)) {
                        $this->logWarning(__('operations.shopping_item.tag_processing'), __('api.shopping.tag_creation_failed'), $request, [
                            'tags' => $request->tags
                        ]);
                        // タグ作成に失敗した場合は、タグなしでアイテム作成を続行
                    } else {
                        $ret->tags()->attach(array_values($tagIds));
                    }
                } catch (Exception $e) {
                    $this->logWarning(__('operations.shopping_item.tag_processing'), __('api.shopping.tag_processing_error'), $request, [
                        'tags' => $request->tags,
                        'error_message' => $e->getMessage()
                    ]);
                    // タグ処理でエラーが発生しても、アイテム作成は成功させる
                }
            }

            DB::commit();

            // タグを含めて再取得
            $ret = $ret->fresh(['tags:id,name']);

            $res = [
                'id' => $ret->id,
                'categoryId' => $ret->category_id,
                'name' => $ret->name,
                'isPinned' => $ret->is_pinned,
                'isChecked' => $ret->is_checked,
                'order' => $ret->order,
                'tags' => $ret->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name
                    ];
                }),
            ];
            return $this->createdResponse($res, __('api.shopping.item_created', ['name' => $request->name]));
        } catch (ValidationException $e) {
            $this->logError(__('operations.shopping_item.store'), $e, $request);
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError(__('operations.shopping_item.store'), $e, $request);
            return $this->handleException($e, $request, __('api.shopping.creation_failed'));
        }
    }

    /**
     * @OA\Put(
     *     path="/shopping-items/bulk",
     *     summary="買い物アイテムを一括更新",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            DB::beginTransaction();

            $updatedItems = [];
            foreach ($request->data as $item) {
                $shoppingItem = ShoppingItem::where('id', $item['id'])->first();
                if (!$shoppingItem) {
                    continue;
                }

                // 基本情報の更新
                $shoppingItem->update([
                    'category_id' => $item['categoryId'],
                    'name' => $item['name'],
                    'is_pinned' => $item['isPinned'],
                    'is_checked' => $item['isChecked'],
                    'order' => $item['order']
                ]);

                // タグの更新
                if (!empty($item['tags'])) {
                    try {
                        $tagIds = $this->findOrCreateIds($item['tags'], $group, ShoppingTag::class);
                        if (empty($tagIds)) {
                            $this->logWarning(__('operations.shopping_item.tag_processing'), __('api.shopping.tag_creation_failed'), $request, [
                                'item_id' => $item['id'],
                                'tags' => $item['tags']
                            ]);
                            // タグ作成に失敗した場合は、タグなしでアイテム更新を続行
                        } else {
                            $shoppingItem->tags()->sync(array_values($tagIds));
                        }
                    } catch (Exception $e) {
                        $this->logError(__('operations.shopping_item.bulk_update'), $e, $request, [
                            'item_id' => $item['id'],
                            'tags' => $item['tags']
                        ]);
                        // タグ処理でエラーが発生しても、アイテム更新は成功させる
                    }
                }

                $updatedItems[] = $shoppingItem;
            }

            DB::commit();

            // 更新したアイテムのみを取得
            $ret = collect($updatedItems)
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'categoryId' => $item->category_id,
                        'name' => $item->name,
                        'isPinned' => $item->is_pinned,
                        'isChecked' => $item->is_checked,
                        'order' => $item->order,
                        'tags' => $item->tags->map(function ($tag) {
                            return [
                                'id' => $tag->id,
                                'name' => $tag->name
                            ];
                        })
                    ];
                });

            return $this->updatedResponse($ret, __('api.shopping.item_bulk_updated', ['count' => $ret->count()]));
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError(__('operations.shopping_item.bulk_update'), $e, $request);
            return $this->handleException($e, $request, __('api.general.bulk_operation_failed'));
        }
    }

    /**
     * @OA\Delete(
     *     path="/shopping-items/bulk",
     *     summary="買い物アイテムを一括削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // IDの配列を取得（単一IDの場合も配列として扱う）
            $ids = $request->ids;

            // 空の場合はエラー
            if (empty($ids)) {
                $this->logError(__('operations.shopping_item.bulk_destroy'), new Exception(__('api.shopping.invalid_ids')), $request);
                return $this->errorResponse(__('api.shopping.invalid_ids'), 400);
            }

            $deletedIds = [];
            $notFoundIds = [];

            foreach ($ids as $id) {
                $item = $group->shoppingItems()->where('id', $id)->first();

                if (!$item) {
                    $notFoundIds[] = $id;
                    continue;
                }

                $deletedIds[] = $item->id;
                $item->delete();
            }

            // 見つからなかったアイテムがある場合は警告ログを出力
            if (!empty($notFoundIds)) {
                $this->logWarning(__('operations.shopping_item.bulk_destroy'), __('api.shopping.some_items_not_found'), $request, [
                    'not_found_ids' => $notFoundIds,
                    'deleted_ids' => $deletedIds
                ]);
            }

            // 削除されたアイテムがない場合はエラー
            if (empty($deletedIds)) {
                $this->logError(__('operations.shopping_item.bulk_destroy'), new Exception(__('api.shopping.no_items_deleted')), $request);
                return $this->errorResponse(__('api.shopping.no_items_deleted'), 400);
            }

            return $this->deletedResponse(__('api.shopping.item_bulk_deleted', ['count' => count($deletedIds)]));
        } catch (Exception $e) {
            $this->logError(__('operations.shopping_item.bulk_destroy'), $e, $request);
            return $this->handleException($e, $request, __('api.shopping.deletion_failed'));
        }
    }
}
