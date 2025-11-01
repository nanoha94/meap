<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\RecipeCategoryBulkDestroyRequest;
use App\Http\Requests\Api\RecipeCategoryBulkUpdateRequest;
use App\Http\Requests\Api\RecipeCategoryIndexRequest;
use App\Http\Requests\Api\RecipeCategoryStoreRequest;
use App\Services\RecipeCategoryService;

class RecipeCategoryController extends ApiController
{
    private RecipeCategoryService $recipeCategoryService;

    public function __construct(RecipeCategoryService $recipeCategoryService)
    {
        $this->recipeCategoryService = $recipeCategoryService;
    }

    /**
     * @OA\Get(
     *     path="/recipe-categories",
     *     summary="料理カテゴリ一覧を取得",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/RecipeCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(RecipeCategoryIndexRequest $request): JsonResponse
    {
        $operation = __('operations.recipe_category.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.recipe_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->recipeCategoryService->index($request->user()->group);
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.recipe_category'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

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
        $operation = __('operations.recipe_category.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.recipe_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->recipeCategoryService->create(
                    $request->validated(),
                    $request->user()->group
                );
                $message = __('api.created', ['attribute' => __('api.attributes.recipe_category'), 'name' => $request->name]);
                return $this->createdResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
        $operation = __('operations.recipe_category.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.recipe_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->recipeCategoryService->bulkUpdate(
                    $request->validated()['data'],
                    $request->user()->group
                );
                $total = count($res);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.recipe_category'), 'count' => $total]);        
                return $this->updatedResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
        $operation = __('operations.recipe_category.bulk_destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.recipe_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $deletedCount = $this->recipeCategoryService->bulkDelete(
                    $request->validated()['ids'],
                    $request->user()->group
                );
$message = __('api.bulk_deleted', ['attribute' => __('api.attributes.recipe_category'), 'count' => $deletedCount]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
