<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingTagController extends Controller
{

    /**
     * @OA\Get(
     *     path="/shopping/tags",
     *     summary="買い物タグ一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingTagIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $group = $user->group;

        $tags = $group->shoppingTags()->select('id', 'name')->get();
        $res = [
            'tags' => $tags,
            'total' => $tags->count()
        ];

        return response()->json($res, 200);
    }
}
