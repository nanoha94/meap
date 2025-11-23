<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ShoppingCategoryBulkDestroyRequest;
use App\Http\Requests\Api\ShoppingCategoryBulkUpdateRequest;
use App\Http\Requests\Api\ShoppingCategoryIndexRequest;
use App\Http\Requests\Api\ShoppingCategoryBulkStoreRequest;
use App\Services\ShoppingCategoryService;
use Illuminate\Http\JsonResponse;

class ShoppingCategoryController extends ApiController
{
    private ShoppingCategoryService $shoppingCategoryService;

    public function __construct(ShoppingCategoryService $shoppingCategoryService)
    {
        $this->shoppingCategoryService = $shoppingCategoryService;
    }

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
    public function index(ShoppingCategoryIndexRequest $request): JsonResponse
    {
        $operation = __('operations.shopping_category.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.shopping.category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingCategoryService->index($this->getUserGroup($request));
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.shopping.category'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/shopping-categories/bulk",
     *     summary="買い物カテゴリを一括作成",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryBulkStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryBulkStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkStore(ShoppingCategoryBulkStoreRequest $request): JsonResponse
    {
        $operation = __('operations.shopping_category.bulk_store');
        $failedMessage = __('api.bulk_creation_failed', ['attribute' => __('api.attributes.shopping.category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingCategoryService->bulkCreate(
                    $request->validated()['data'],
                    $this->getUserGroup($request)
                );
                $message = __('api.bulk_created', ['attribute' => __('api.attributes.shopping.category'), 'count' => count($res)]);
                return $this->createdResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function bulkUpdate(ShoppingCategoryBulkUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.shopping_category.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.shopping.category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingCategoryService->bulkUpdate(
                    $request->validated()['data'],
                    $this->getUserGroup($request)
                );
                $total = count($res);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.shopping.category'), 'count' => $total]);
                return $this->updatedResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function bulkDestroy(ShoppingCategoryBulkDestroyRequest $request): JsonResponse
    {
        $operation = __('operations.shopping_category.bulk_destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.shopping.category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $validated = $request->validated();

                $deletedCount = $this->shoppingCategoryService->bulkDelete(
                    $validated['ids'],
                    $this->getUserGroup($request)
                );
                $message = __('api.bulk_deleted', ['attribute' => __('api.attributes.shopping.category'), 'count' => $deletedCount]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
