<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\MealType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\HttpStatusCode;

class MealTypeController extends ApiController
{
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
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $ret = MealType::create([
                'group_id' => $group->id,
                'name' => $request->name,
                'color_id' => $request->colorId,
                'order' => $group->mealTypes->count(),
            ]);

            $res = [
                'id' => $ret->id,
                'name' => $ret->name,
                'colorId' => $ret->color_id,
                'order' => $ret->order,
            ];
            return $this->createdResponse($res, __('api.meal_type.created', ['name' => $ret->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_type.creation_failed'),
                'meal_type.store',
            );
        }
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
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            foreach ($request->categories as $category) {
                $data = MealType::find($category['id']);
                if (!$data) {
                    continue;
                }

                $data->update([
                    'name' => $category['name'],
                    'color_id' => $category['colorId'],
                    'order' => $data->order
                ]);
            }

            $types = $group->mealTypes()->select('id', 'name', 'color_id', 'order')->get();
            $ret = $types->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'colorId' => $type->color_id,
                    'order' => $type->order
                ];
            });

            return $this->updatedResponse($ret, __('api.meal_type.updated', ['count' => $types->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_type.update_failed'),
                'meal_type.bulk_update',
            );
        }
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $mealType =  MealType::where('id', $id)->where('group_id', $group->id)->first();

            if (!$mealType) {
                $this->logError(HttpStatusCode::NOT_FOUND, __('operations.meal_type.destroy'), new Exception(__('api.general.not_found')), $request, [
                    'meal_type_id' => $id
                ]);
                return $this->errorResponse(__('api.general.not_found'), HttpStatusCode::NOT_FOUND);
            }

            $deletedId = $mealType->id;
            $mealType->delete();

            // 残りのカテゴリーのorderを整理
            $remainingTypes = MealType::where('group_id', $mealType->group_id)
                ->orderBy('order')
                ->get();

            foreach ($remainingTypes as $index => $remainingType) {
                $remainingType->update(['order' => $index]);
            }

            return $this->deletedResponse(__('api.meal_type.deleted', ['name' => $mealType->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.meal_type.deletion_failed'),
                'meal_type.destroy'
            );
        }
    }
}
