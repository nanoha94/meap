<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\MealCategoryBulkUpdateRequest;
use App\Http\Requests\Api\MealCategoryDestroyRequest;
use App\Http\Requests\Api\MealCategoryIndexRequest;
use App\Http\Requests\Api\MealCategoryStoreRequest;
use App\Services\MealCategoryService;

class MealCategoryController extends ApiController
{
    private MealCategoryService $mealCategoryService;

    public function __construct(MealCategoryService $mealCategoryService)
    {
        $this->mealCategoryService = $mealCategoryService;
    }

    /**
     * @OA\Get(
     *     path="/meal-categories",
     *     summary="献立カテゴリ一覧を取得",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(MealCategoryIndexRequest $request): JsonResponse
    {
        $operation = __('operations.meal_category.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.meal_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->mealCategoryService->index($this->getUserGroup($request));
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.meal_category'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/meal-categories",
     *     summary="献立カテゴリを作成",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(MealCategoryStoreRequest $request): JsonResponse
    {
        $operation = __('operations.meal_category.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.meal_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $this->mealCategoryService->create($request->validated(), $this->getUserGroup($request));
                $message = __('api.created', ['attribute' => __('api.attributes.meal_category'), 'name' => $request->name]);
                return $this->createdResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Put(
     *     path="/meal-categories/bulk",
     *     summary="献立カテゴリを更新",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(MealCategoryBulkUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.meal_category.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.meal_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $updatedData = $this->mealCategoryService->bulkUpdate(
                    $request->validated()['data'],
                    $this->getUserGroup($request)
                );
                $total = count($updatedData);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.meal_category'), 'count' => $total]);
                return $this->updatedResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Delete(
     *     path="/meal-categories/{id}",
     *     summary="献立カテゴリを削除",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(MealCategoryDestroyRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.meal_category.destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.meal_category')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $deletedMealCategory = $this->mealCategoryService->delete($id, $this->getUserGroup($request));
                $message = __('api.deleted', ['attribute' => __('api.attributes.meal_category'), 'name' => $deletedMealCategory->name]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
