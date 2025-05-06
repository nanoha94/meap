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
     *     summary="買い物カテゴリー一覧を取得",
     *     description="買い物カテゴリーの一覧を返します。",
     *     tags={"Shopping"}, 
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="isDefault", type="boolean"),
     *                 @OA\Property(property="order", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $res = [];
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

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
     *     summary="買い物カテゴリーを作成",
     *     description="新しい買い物カテゴリーを作成します。",
     *     tags={"Shopping"},       
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="食料品")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent( 
     *             type="object",
     *             @OA\Property(property="message", type="string", example="買い物カテゴリーを作成しました。")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

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
     *     path="/shopping/categories/{id}",
     *     summary="買い物カテゴリーを更新",
     *     description="指定された買い物カテゴリーを更新します。",
     *     tags={"Shopping"},   
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="買い物カテゴリーのID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="食料品"),
     *             @OA\Property(property="order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(     
     *             type="object",
     *             @OA\Property(property="message", type="string", example="買い物カテゴリーを更新しました。")
     *         )
     *     )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        return response()->json(['message' => '買い物カテゴリーを更新しました。']);
    }

    /**
     * @OA\Delete(
     *     path="/shopping/categories/{id}",
     *     summary="買い物カテゴリーを削除",
     *     description="指定された買い物カテゴリーを削除します。",
     *     tags={"Shopping"},       
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="買い物カテゴリーのID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="買い物カテゴリーを削除しました。")
     *         )
     *     )
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
