<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\MealTypeBulkUpdateRequest;
use App\Http\Requests\Api\MealTypeDestroyRequest;
use App\Http\Requests\Api\MealTypeIndexRequest;
use App\Http\Requests\Api\MealTypeStoreRequest;
use App\Services\MealTypeService;

class MealTypeController extends ApiController
{
    private MealTypeService $mealTypeService;

    public function __construct(MealTypeService $mealTypeService)
    {
        $this->mealTypeService = $mealTypeService;
    }

    /**
     * @OA\Get(
     *     path="/meal-types",
     *     summary="献立種別一覧を取得",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MealTypeIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(MealTypeIndexRequest $request): JsonResponse
    {
        $operation = __('operations.meal_type.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.meal_type')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->mealTypeService->index($request->user()->group);
                $total = count($res );  
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.meal_type'), 'count' => $total]);   
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/meal-types",
     *     summary="献立種別を作成",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealTypeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealTypeStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(MealTypeStoreRequest $request): JsonResponse
    {
        $operation = __('operations.meal_type.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.meal_type')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->mealTypeService->create($request->validated(), $request->user()->group);
                $message = __('api.created', ['attribute' => __('api.attributes.meal_type'), 'name' => $res['name']]);
                return $this->createdResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Put(
     *     path="/meal-types/bulk",
     *     summary="献立種別を更新",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealTypeBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealTypeBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(MealTypeBulkUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.meal_type.bulk_update');
        $failedMessage = __('api.bulk_update_failed', ['attribute' => __('api.attributes.meal_type')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $updatedData = $this->mealTypeService->bulkUpdate(
                    $request->validated()['data'],
                    $request->user()->group
                );
                $total = count($updatedData);
                $message = __('api.bulk_updated', ['attribute' => __('api.attributes.meal_type'), 'count' => $total]);
                return $this->updatedResponse($updatedData, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Delete(
     *     path="/meal-types/{id}",
     *     summary="献立種別を削除",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealTypeIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealTypeDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(MealTypeDestroyRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.meal_type.destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.meal_type')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $deletedMealType = $this->mealTypeService->delete($id, $request->user()->group);
                $message = __('api.deleted', ['attribute' => __('api.attributes.meal_type'), 'name' => $deletedMealType->name]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
