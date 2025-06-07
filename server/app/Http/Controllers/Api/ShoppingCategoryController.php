<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $data = [];
        $user = $request->user();
        $group = $user->group;

        $categories = $group->shoppingCategories->select('id', 'name', 'is_default', 'order');

        $res = ['categories' => $categories, 'total' => $categories->count()];

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
        $group = $user->group;

        $ret = ShoppingCategory::create([
            'group_id' => $group->id,
            'name' => $request->name,
            'is_default' => false,
            'order' => $group->shoppingCategories->count(),
        ]);

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
            'is_default' => $ret->is_default,
            'order' => $ret->order
        ], 200);
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
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        foreach ($request->categories as $item) {
            ShoppingCategory::where('id', $item['id'])->update([
                'name' => $item['name'],
                'is_default' => $item['is_default'],
                'order' => $item['order']
            ]);
        }

        $ret = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->get();

        return response()->json($ret, 200);
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        $category =  ShoppingCategory::where('id', $id)->first();

        if (!$category) {
            return response()->json([
                'message' => '指定されたカテゴリーが見つかりません。'
            ], 404);
        }
        if ($category->is_default) {
            return response()->json([
                'message' => $category->name . 'は削除できません。'
            ], 403);
        }

        $id = $category->id;
        $category->delete();

        // 残りのカテゴリーのorderを整理
        $remainingCategories = ShoppingCategory::where('group_id', $category->group_id)
            ->orderBy('order')
            ->get();

        foreach ($remainingCategories as $index => $remainingCategory) {
            $remainingCategory->update(['order' => $index]);
        }

        return response()->json(['id' => $id], 200);
    }
}
