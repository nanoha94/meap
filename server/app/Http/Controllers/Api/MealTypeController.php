<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\MealType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealTypeController extends Controller
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

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
            'colorId' => $ret->color_id,
            'order' => $ret->order,
        ], 200);
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

        return response()->json($ret, 200);
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
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }
        if ($type->is_default) {
            return response()->json([
                'message' => $type->name . 'は削除できません。'
            ], 403);
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

        return response()->json(['id' => $deletedId], 200);
    }
}
