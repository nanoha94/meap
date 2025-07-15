<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShoppingCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/shopping-categories",
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
        $user = $request->user();
        $group = $user->group;

        try {
            $categories = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->orderBy('order', 'asc')->get();
            $res = [
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'isDefault' => (bool)$category->is_default,
                        'order' => $category->order
                    ];
                }),
                'total' => $categories->count()
            ];

            return response()->json($res, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => '買い物カテゴリーの取得中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/shopping-categories",
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

        // 入力値のバリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        try {
            $ret = ShoppingCategory::create([
                'group_id' => $group->id,
                'name' => $request->name,
                'is_default' => false,
                'order' => $request->order,
            ]);

            // 作成が失敗した場合のエラー処理
            if (!$ret) {
                return response()->json([
                    'message' => '買い物カテゴリー（' . $request->name . '）の作成に失敗しました。'
                ], 500);
            }

            return response()->json([
                'id' => $ret->id,
                'name' => $ret->name,
                'isDefault' => (bool)$ret->is_default,
                'order' => $ret->order
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => '買い物カテゴリー（' . $request->name . '）の作成中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/shopping-categories/bulk",
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

        // 入力値のバリデーション
        $request->validate([
            'data' => 'required|array',
            'data.*.id' => 'required|string|max:255',
            'data.*.name' => 'required|string|max:255',
            'data.*.isDefault' => 'required|boolean',
        ]);

        try {
            // 更新処理を実行
            foreach ($request->data as $category) {
                $ret = ShoppingCategory::where('id', $category['id'])->update([
                    'name' => $category['name'],
                    'is_default' => $category['isDefault'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    return response()->json([
                        'message' => '買い物カテゴリー（' . $category['name'] . '）の更新に失敗しました。'
                    ], 500);
                }
            }

            // 更新後のカテゴリーを全て取得
            $categories = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->get();
            $ret = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'isDefault' => (bool)$category->is_default,
                    'order' => $category->order
                ];
            });

            return response()->json($ret, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => '買い物カテゴリーの一括更新中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/shopping-categories/bulk",
     *     summary="買い物カテゴリを削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        // 入力値のバリデーション
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|max:255',
        ]);

        $deletedIds = [];
        foreach ($request->ids as $id) {
            $category = ShoppingCategory::where('id', $id)->where('group_id', $group->id)->first();

            if (!$category) {
                Log::error('指定されたレコードが見つかりません。', ['function' => 'ShoppingCategoryController@bulkDestroy', 'id' => $id]);
                return response()->json([
                    'message' => '指定されたレコードが見つかりません。'
                ], 404);
            }

            if ($category->is_default) {
                return response()->json([
                    'message' => $category->name . 'は削除できません。'
                ], 403);
            }

            $deletedIds[] = [$category->id];
            $category->delete();
        }

        // 残りのカテゴリーのorderを整理
        $remainingCategories = ShoppingCategory::where('group_id', $category->group_id)
            ->orderBy('order')
            ->get();

        foreach ($remainingCategories as $index => $remainingCategory) {
            $remainingCategory->update(['order' => $index]);
        }

        return response()->json(['ids' => $deletedIds], 200);
    }
}
