<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\MealType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        return $this->createdResponse($res, '献立種別(' . $ret->name . ')を作成しました。');
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

        return $this->updatedResponse($ret, '献立種別を' . $types->count() . '件更新しました。');
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
        $user = $request->user();
        $group = $user->group;

        $type =  MealType::where('id', $id)->where('group_id', $group->id)->first();

        if (!$type) {
            return $this->notFoundResponse('指定されたレコードが見つかりません。');
        }
        if ($type->is_default) {
            return $this->errorResponse($type->name . 'は削除できません。', 403);
        }

        $deletedId = $type->id;
        $type->delete();

        // 残りのカテゴリーのorderを整理
        $remainingTypes = MealType::where('group_id', $type->group_id)
            ->orderBy('order')
            ->get();

        foreach ($remainingTypes as $index => $remainingType) {
            $remainingType->update(['order' => $index]);
        }

        return $this->deletedResponse('献立種別(' . $type->name . ')を削除しました。');
    }
}
