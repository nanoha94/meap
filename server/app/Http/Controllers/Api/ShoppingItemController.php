<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ShoppingItemStoreRequest;
use App\Http\Requests\Api\ShoppingItemBulkUpdateRequest;
use App\Http\Requests\Api\ShoppingItemBulkDestroyRequest;
use App\Models\ShoppingItem;
use App\Models\ShoppingTag;
use App\Services\ShoppingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Enums\HttpStatusCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

            return $this->indexResponse($res, $categories->count(), __('api.list_retrieved', ['attribute' => __('api.attributes.shopping.item'), 'count' => $categories->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.get_failed', ['attribute' => __('api.attributes.shopping.list')]),
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
    public function store(ShoppingItemStoreRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $validated = $request->validated();

            DB::beginTransaction();

            $ret = ShoppingItem::create([
                'group_id' => $group->id,
                'category_id' => $validated['categoryId'],
                'name' => $validated['name'],
                'is_pinned' => false,
                'is_checked' => false,
                'order' => $group->shoppingItems->where('category_id', $validated['categoryId'])->count()
            ]);
            if (!$ret) {
                DB::rollBack();
                $this->handleException(
                    new HttpException(HttpStatusCode::INTERNAL_SERVER_ERROR->value, __('api.creation_failed', ['attribute' => __('api.attributes.shopping.item')])),
                    $request,
                    __('api.creation_failed', ['attribute' => __('api.attributes.shopping.item')]),
                    __('operations.shopping_item.store')
                );
            }

            // タグの処理
            if (!empty($validated['tags'])) {
                $tagIds = $this->shoppingService->processTags($validated['tags'], $group, ShoppingTag::class);
                if (empty($tagIds)) {
                    $this->logWarning(HttpStatusCode::OK, __('operations.shopping_item.tag_processing'), __('api.creation_failed', ['attribute' => __('api.attributes.tag')]), $request, [
                        'tags' => $validated['tags']
                    ],  __METHOD__);
                } else {
                    $ret->tags()->attach($tagIds);
                }
            }

            DB::commit();

            // タグを含めて再取得
            $ret = $ret->fresh(['tags:id,name']);

            $res = $this->shoppingService->formatCompleteShoppingItemResponse($ret);
            return $this->createdResponse($res, __('api.created', ['attribute' => __('api.attributes.shopping.item'), 'name' => $validated['name']]));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException(
                $e,
                $request,
                __('api.creation_failed', ['attribute' => __('api.attributes.shopping.item')]),
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
    public function bulkUpdate(ShoppingItemBulkUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $validated = $request->validated();

            DB::beginTransaction();

            $updatedItems = [];
            foreach ($validated['data'] as $data) {
                $shoppingItem = ShoppingItem::where('id', $data['id'])->first();
                if (!$shoppingItem) {
                    continue;
                }

                // 基本情報の更新
                $shoppingItem->update([
                    'category_id' => $data['categoryId'],
                    'name' => $data['name'],
                    'is_pinned' => $data['isPinned'] ?? false,
                    'is_checked' => $data['isChecked'] ?? false,
                    'order' => $data['order'] ?? 0
                ]);

                // タグの更新
                if (!empty($data['tags'])) {
                    $tagIds = $this->shoppingService->processTags($data['tags'], $group, ShoppingTag::class);
                    if (empty($tagIds)) {
                        $this->logWarning(HttpStatusCode::OK, __('operations.shopping_item.tag_processing'), __('api.creation_failed', ['attribute' => __('api.attributes.shopping.tag')]), $request, [
                            'item_id' => $data['id'],
                            'tags' => $data['tags']
                        ],  __METHOD__);
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

            return $this->updatedResponse($ret, __('api.bulk_updated', ['attribute' => __('api.attributes.shopping.item'), 'count' => $ret->count()]));
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
    public function bulkDestroy(ShoppingItemBulkDestroyRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $validated = $request->validated();
            $ids = $validated['ids'];

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
                $this->logWarning(HttpStatusCode::NOT_FOUND, __('operations.shopping_item.bulk_destroy'), new Exception(__('api.some_items_not_found', ['attribute' => __('api.attributes.shopping.item')])), $request, [
                    'not_found_ids' => $notFoundIds,
                    'deleted_ids' => $deletedIds
                ],  __METHOD__);
            }

            // 削除されたアイテムがない場合はエラー
            if (empty($deletedIds)) {
                $this->handleException(
                    new HttpException(HttpStatusCode::BAD_REQUEST->value, __('api.no_items_deleted', ['attribute' => __('api.attributes.shopping.item')])),
                    $request,
                    __('api.no_items_deleted', ['attribute' => __('api.attributes.shopping.item')]),
                    __('operations.shopping_item.bulk_destroy')
                );
            }

            return $this->deletedResponse(__('api.bulk_deleted', ['attribute' => __('api.attributes.shopping.item'), 'count' => count($deletedIds)]));
        } catch (Exception $e) {
            return $this->handleException($e, $request, __('api.deletion_failed', ['attribute' => __('api.attributes.shopping.item')]), 'shopping_item.bulk_destroy');
        }
    }
}
