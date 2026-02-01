<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\IngredientCategoryIndexRequest;
use App\Http\Requests\Api\IngredientCategoryBulkDestroyRequest;
use App\Http\Requests\Api\IngredientCategoryBulkUpdateRequest;
use App\Http\Requests\Api\IngredientCategoryBulkStoreRequest;
use App\Services\IngredientCategoryService;

class IngredientCategoryController extends ApiController
{
    public function __construct(
        private IngredientCategoryService $ingredientCategoryService
    ) {}

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
    public function index(IngredientCategoryIndexRequest $request): JsonResponse
    {
        $operation = __('operations.ingredient_category.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.ingredient_category')]);
        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->ingredientCategoryService->index($this->getUserGroup($request));
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.ingredient_category'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/ingredient-categories/bulk",
     *     summary="食材カテゴリを一括作成",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryBulkStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryBulkStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkStore(IngredientCategoryBulkStoreRequest $request): JsonResponse
    {
        $operation = __('operations.ingredient_category.bulk_store');
        $failedMessage = __('api.bulk_creation_failed', ['attribute' => __('api.attributes.ingredient_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->ingredientCategoryService->bulkCreate(
                    $request->validated()['data'],
                    $this->getUserGroup($request)
                );

                $message = __('api.bulk_created', ['attribute' => __('api.attributes.ingredient_category'), 'count' => count($res)]);
                return $this->createdResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function bulkUpdate(IngredientCategoryBulkUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.ingredient_category.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.ingredient_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $updatedData = $this->ingredientCategoryService->bulkUpdate(
                    $request->validated()['data'],
                    $this->getUserGroup($request)
                );

                $total = count($updatedData);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.ingredient_category'), 'count' => $total]);
                return $this->updatedResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function bulkDestroy(IngredientCategoryBulkDestroyRequest $request): JsonResponse
    {
        $operation = __('operations.ingredient_category.bulk_destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.ingredient_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $deletedCount = $this->ingredientCategoryService->bulkDelete(
                    $request->validated()['ids'],
                    $this->getUserGroup($request)
                );
                $message = __('api.bulk_deleted', ['attribute' => __('api.attributes.ingredient_category'), 'count' => $deletedCount]);

                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
