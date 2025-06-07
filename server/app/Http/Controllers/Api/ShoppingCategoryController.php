<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/shopping/categories",
     *     summary="買い物カテゴリ一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $res = [];
        $user = $request->user();
        $groupId = $user->group_id;

        $categories = ShoppingCategory::where('group_id', $groupId)->get();

        foreach ($categories as $idx => $category) {
            $res[$idx] = [
                'id' => $category->id,
                'name' => $category->name,
                'isDefault' => (bool)$category->is_default,
                'order' => $category->order
            ];
        }

        return response()->json($res, 200);
    }

    /**
     * @OA\Post(
     *     path="/shopping/categories",
     *     summary="買い物カテゴリを作成",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->group_id;

        // TODO: 要修正
        ShoppingCategory::upsert([
            'id' => $request->id,
            'group_id' => $groupId,
            'name' => $request->name,
            'is_default' => false,
            'order' => $request->order,
        ], uniqueBy: ['id'], update: ['name', 'order']);

        return response()->json(['message' => '買い物カテゴリーを作成しました。']);
    }

    /**
     * @OA\Put(
     *     path="/shopping/categories/bulk",
     *     summary="買い物カテゴリを一括更新",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request): JsonResponse
    {
        return response()->json(['message' => '買い物カテゴリーを更新しました。']);
    }

    /**
     * @OA\Delete(
     *     path="/shopping/categories/{id}",
     *     summary="買い物カテゴリを削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/ShoppingCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        $category =  ShoppingCategory::where('id', $request->id)->first();
        $category_name = $category->name;
        $category->delete();
        return response()->json(['message' => $category_name . 'を買い物カテゴリーから削除しました。'], 200);
    }
}
