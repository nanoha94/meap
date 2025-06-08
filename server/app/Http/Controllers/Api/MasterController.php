<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/master",
     *     summary="マスターデータを取得",
     *     tags={"Master"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MasterSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $shopping_ategories = $group->shoppingCategories()->select('id', 'name')->get();
        $shopping_tags = $group->shoppingTags()->select('id', 'name')->get();
        $res = [
            'dishCategories' => [],
            'shoppingCategories' => $shopping_ategories,
            'shoppingTags' => $shopping_tags,
        ];

        return response()->json($res, 200);
    }
}
