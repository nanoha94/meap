<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\ShoppingItem;
use App\Models\ShoppingTag;
use App\Services\ShoppingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Enums\HttpStatusCode;

class ShoppingItemController extends ApiController
{
    use AutoComplement;

    protected ShoppingService $shoppingService;

    public function __construct(ShoppingService $shoppingService)
    {
        $this->shoppingService = $shoppingService;
    }

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
                    'category' => $this->shoppingService->formatShoppingCategory($category),
                    'items' => $items->map(function ($item) {
                        return $this->shoppingService->formatShoppingItem($item);
                    })
                ];
            }

            return $this->indexResponse($res, $categories->count(), __('api.shopping.list_retrieved', ['count' => $categories->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.shopping.list_get_failed'),
                'shopping_item.index',
            );
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
                return $this->errorResponse(__('api.shopping.creation_failed'), HttpStatusCode::INTERNAL_SERVER_ERROR);
            }

            // タグの処理
            if (!empty($request->tags)) {
                $tagIds = $this->shoppingService->processTags($request->tags, $group, ShoppingTag::class);
                if (empty($tagIds)) {
                    $this->logWarning(__('operations.shopping_item.tag_processing'), __('api.shopping.tag_creation_failed'), $request, [
                        'tags' => $request->tags
                    ]);
                } else {
                    $ret->tags()->attach($tagIds);
                }
            }

            DB::commit();

            // タグを含めて再取得
            $ret = $ret->fresh(['tags:id,name']);

            $res = $this->shoppingService->formatCompleteShoppingItemResponse($ret);
            return $this->createdResponse($res, __('api.shopping.item_created', ['name' => $request->name]));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException(
                $e,
                $request,
                __('api.shopping.creation_failed'),
                'shopping_item.store'
            );
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
                    $tagIds = $this->shoppingService->processTags($item['tags'], $group, ShoppingTag::class);
                    if (empty($tagIds)) {
                        $this->logWarning(__('operations.shopping_item.tag_processing'), __('api.shopping.tag_creation_failed'), $request, [
                            'item_id' => $item['id'],
                            'tags' => $item->tags
                        ]);
                    } else {
                        $shoppingItem->tags()->sync($tagIds);
                    }
                }

                $updatedItems[] = $shoppingItem;
            }

            DB::commit();

            // 更新したアイテムのみを取得
            $ret = collect($updatedItems)
                ->map(function ($item) {
                    return $this->shoppingService->formatCompleteShoppingItemResponse($item);
                });

            return $this->updatedResponse($ret, __('api.shopping.item_bulk_updated', ['count' => $ret->count()]));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException(
                $e,
                $request,
                __('api.general.bulk_operation_failed'),
                'shopping_item.bulk_update'
            );
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
                return $this->errorResponse(__('api.shopping.invalid_ids'), HttpStatusCode::BAD_REQUEST);
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
                $this->logError(__('operations.shopping_item.bulk_destroy'), new Exception(__('api.shopping.some_items_not_found')), $request, [
                    'not_found_ids' => $notFoundIds,
                    'deleted_ids' => $deletedIds
                ]);
            }

            // 削除されたアイテムがない場合はエラー
            if (empty($deletedIds)) {
                $this->logError(__('operations.shopping_item.bulk_destroy'), new Exception(__('api.shopping.no_items_deleted')), $request);
                return $this->errorResponse(__('api.shopping.no_items_deleted'), HttpStatusCode::BAD_REQUEST);
            }

            return $this->deletedResponse(__('api.shopping.item_bulk_deleted', ['count' => count($deletedIds)]));
        } catch (Exception $e) {
            $this->logError(__('operations.shopping_item.bulk_destroy'), $e, $request);
            return $this->handleException($e, $request, __('api.shopping.deletion_failed'));
        }
    }
}
