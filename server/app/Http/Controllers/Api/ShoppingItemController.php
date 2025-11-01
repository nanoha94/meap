<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ShoppingItemStoreRequest;
use App\Http\Requests\Api\ShoppingItemBulkUpdateRequest;
use App\Http\Requests\Api\ShoppingItemBulkDestroyRequest;
use App\Http\Requests\Api\ShoppingItemIndexRequest;
use App\Services\ShoppingItemService;
use Illuminate\Http\JsonResponse;

class ShoppingItemController extends ApiController
{
    protected ShoppingItemService $shoppingItemService;

    public function __construct(ShoppingItemService $shoppingItemService)
    {
        $this->shoppingItemService = $shoppingItemService;
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
    public function index(ShoppingItemIndexRequest $request): JsonResponse
    {
        $operation = __('operations.shopping_item.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.shopping.list')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingItemService->indexGroupedByCategory($this->getUserGroup($request));
                $total = collect($res)->sum(fn($categoryData) => count($categoryData['items']));
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.shopping.item'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
        $operation = __('operations.shopping_item.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.shopping.item')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingItemService->create(
                    $request->validated(),
                    $this->getUserGroup($request)
                );

                $message = __('api.created', ['attribute' => __('api.attributes.shopping.item'), 'name' => $request->name]);
                return $this->createdResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
        $operation = __('operations.shopping_item.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.shopping.item')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $validated = $request->validated();
                $res = $this->shoppingItemService->bulkUpdate(
                    $validated['data'],
                    $this->getUserGroup($request)
                );
                $total = count($res);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.shopping.item'), 'count' => $total]);
                return $this->updatedResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
        $operation = __('operations.shopping_item.bulk_destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.shopping.item')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $validated = $request->validated();
                $deletedCount = $this->shoppingItemService->bulkDelete(
                    $validated['ids'],
                    $this->getUserGroup($request)
                );
                $message = __('api.bulk_deleted', ['attribute' => __('api.attributes.shopping.item'), 'count' => $deletedCount]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
