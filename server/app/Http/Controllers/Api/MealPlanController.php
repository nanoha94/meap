<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\MealPlanStoreRequest;
use App\Models\MealPlan;
use App\Services\MealPlanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // TODO: 月別に取得できるようにする
            $meal_plans = $group->mealPlans()
                ->select('id', 'date', 'meal_type_id')
                ->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.ingredients'])
                ->get()
                ->groupBy('date')
                ->values();

            $res = [
                'mealPlans' => $meal_plans->map(function ($dateMeals, $date) {
                    return [
                        'date' => $date,
                        'mealPlans' => $dateMeals->map(function ($mealPlan) {
                            return $this->mealPlanService->formatCompleteMealPlanResponse($mealPlan);
                        })
                    ];
                })->values()
            ];

            return $this->indexResponse($res, $meal_plans->count(), __('api.meal_plan.list_retrieved', ['count' => $meal_plans->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_plan.get_failed'),
                'meal_plan.index',
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/meal-plans",
     *     summary="献立を作成",
     *     description="献立を作成します。",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(MealPlanStoreRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $ret = MealPlan::create([
                'group_id' => $group->id,
                'meal_type_id' => $request->mealTypeId,
                'date' => $request->date,
            ]);

            // 料理を紐づけ
            if (!empty($request->menu)) {
                foreach ($request->menu as $item) {
                    if (!isset($item['recipeIds'])) {
                        continue;
                    }
                    $data = collect($item['recipeIds'])->unique()->map(function ($recipeId) use ($ret, $item) {
                        return [
                            'meal_plan_id' => $ret->id,
                            'recipe_id' => $recipeId,
                            'course_type_id' => $item['courseTypeId']
                        ];
                    })->toArray();
                    $ret->recipes()->attach($data);
                }
            }

            $res = $this->mealPlanService->formatCompleteMealPlanResponse($ret);
            return $this->createdResponse($res, __('api.meal_plan.created', ['date' => $res['date']]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_plan.creation_failed'),
                'meal_plan.store',

            );
        }
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
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $meal = MealPlan::where('id', $id)->where('group_id', $group->id)->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.ingredients'])->first();
            if (!$meal) {
                return $this->notFoundResponse(__('api.meal_plan.not_found'));
            }

            $res = $this->mealPlanService->formatCompleteMealPlanResponse($meal);

            return $this->showResponse($res, __('api.meal_plan.retrieved', ['date' => $meal->date]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_plan.get_failed'),
                'meal_plan.show',
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/meal-plans/{id}",
     *     summary="献立を更新",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $meal =  MealPlan::where('id', $id)->where('group_id', $group->id)->first();
            if (!$meal) {
                return $this->notFoundResponse(__('api.meal_plan.not_found'));
            }

            $meal->update([
                'group_id' => $group->id,
                'meal_type_id' => $request->mealTypeId,
                'date' => $request->date,
            ]);

            // 料理更新
            if (!empty($request->menu)) {
                foreach ($request->menu as $item) {
                    if (!isset($item['recipeIds'])) {
                        continue;
                    }
                    $data = collect($item['recipeIds'])
                        ->unique()
                        ->map(function ($recipeId) use ($meal, $item) {
                            return [
                                'meal_plan_id' => $meal->id,
                                'recipe_id' => $recipeId,
                                'course_type_id' => $item['courseTypeId']
                            ];
                        })->toArray();
                    $meal->recipes()->sync($data);
                }
            }

            $updatedItem = $group->mealPlans()->where('id', $id)->first()->select('id', 'date', 'meal_type_id')->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.ingredients'])->first();

            $res = $this->mealPlanService->formatCompleteMealPlanResponse($updatedItem);
            return $this->updatedResponse($res, __('api.meal_plan.updated', ['date' => $updatedItem->date]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_plan.update_failed'),
                'meal_plan.update',
                ['meal_plan_id' => $id]
            );
        }
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $meal =  MealPlan::where('id', $id)->where('group_id', $group->id)->first();

            if (!$meal) {
                return $this->notFoundResponse(__('api.meal_plan.not_found'));
            }

            $meal->delete();

            return $this->deletedResponse(__('api.meal_plan.deleted', ['date' => $meal->date]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_plan.deletion_failed'),
                'meal_plan.destroy',
            );
        }
    }
}
