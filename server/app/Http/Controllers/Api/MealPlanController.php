<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\MealPlanDestroyRequest;
use App\Http\Requests\Api\MealPlanIndexRequest;
use App\Http\Requests\Api\MealDestroyRequest;
use App\Http\Requests\Api\MealPlanShowRequest;
use App\Http\Requests\Api\MealPlanStoreRequest;
use App\Http\Requests\Api\MealPlanUpdateRequest;
use App\Services\MealPlanService;
use Illuminate\Http\JsonResponse;

class MealPlanController extends ApiController
{
    protected MealPlanService $mealPlanService;

    public function __construct(MealPlanService $mealPlanService)
    {
        $this->mealPlanService = $mealPlanService;
    }

    /**
     * @OA\Get(
     *     path="/meal-plans",
     *     summary="献立一覧を取得",
     *     description="指定した年・月の献立一覧を取得します。year と month は必須のクエリパラメータです。",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIndexYearParam"),
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIndexMonthParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(MealPlanIndexRequest $request): JsonResponse
    {
        $operation = __('operations.meal_plan.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.meal_plan')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $validated = $request->validated();
                $res = $this->mealPlanService->indexForMonth(
                    $this->getUserGroup($request),
                    (int) $validated['year'],
                    (int) $validated['month'],
                );
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.meal_plan'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/meal-plans",
     *     summary="献立を作成",
     *     description="献立を作成します。",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(MealPlanStoreRequest $request): JsonResponse
    {
        $operation = __('operations.meal_plan.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.meal_plan')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $this->mealPlanService->create($request->validated(), $this->getUserGroup($request));
                $message = __('api.created', ['attribute' => __('api.attributes.meal_plan'), 'name' => $request->input('date')]);
                return $this->createdResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Get(
     *     path="/meal-plans/{id}",
     *     summary="献立の詳細を取得",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(MealPlanShowRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.meal_plan.show');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.meal_plan')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $res = $this->mealPlanService->show($id, $this->getUserGroup($request));
                $message = __('api.retrieved', ['attribute' => __('api.attributes.meal_plan'), 'name' => $res['date']]);
                return $this->showResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Put(
     *     path="/meal-plans/{id}",
     *     summary="献立を更新",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(MealPlanUpdateRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.meal_plan.update');
        $failedMessage = __('api.update_failed', ['attribute' => __('api.attributes.meal_plan')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $mealPlan = $this->mealPlanService->update($id, $request->validated(), $this->getUserGroup($request));
                $message = __('api.updated', ['attribute' => __('api.attributes.meal_plan'), 'name' => $mealPlan->date]);
                return $this->updatedResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation,
            ['meal_plan_id' => $id]
        );
    }

    /**
     * @OA\Delete(
     *     path="/meal-plans/{id}",
     *     summary="献立を削除",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(MealPlanDestroyRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.meal_plan.destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.meal_plan')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $deletedMealPlan = $this->mealPlanService->delete($id, $this->getUserGroup($request));
                $message = __('api.deleted', ['attribute' => __('api.attributes.meal_plan'), 'name' => $deletedMealPlan->date]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Delete(
     *     path="/meal-plans/{mealPlanId}/meals/{mealId}",
     *     summary="献立の1食を削除",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdPathParam"),
     *     @OA\Parameter(ref="#/components/parameters/MealIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanMealDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroyMeal(MealDestroyRequest $request, string $mealPlanId, string $mealId): JsonResponse
    {
        $operation = __('operations.meal_plan.destroy_meal');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.meal')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $mealPlanId, $mealId) {
                $deletedMeal = $this->mealPlanService->deleteMeal($mealPlanId, $mealId, $this->getUserGroup($request));
                $message = __('api.deleted', ['attribute' => __('api.attributes.meal_plan'), 'name' => $deletedMeal->mealPlan->date . ' / ' . $deletedMeal->mealCategory->name]);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
