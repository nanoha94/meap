<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingItemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/shopping/items",
     *     summary="買い物アイテム一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $res = [];
        $categories = $group->shoppingCategories()
            ->select('id', 'name', 'is_default as isDefault', 'order')
            ->orderBy('order', 'asc')
            ->get();

        foreach ($categories as $category) {
            $items = $category->shoppingItems()
                ->select('id', 'name', 'is_pinned', 'is_checked', 'category_id', 'order')
                ->orderBy('order', 'asc')
                ->get();

            $res[] = [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'isDefault' => $category->is_default,
                    'order' => $category->order
                ],
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'isPinned' => (bool)$item->is_pinned,
                        'isChecked' => (bool)$item->is_checked,
                        'categoryId' => $item->category_id,
                        'order' => $item->order
                    ];
                })
            ];
        }

        return response()->json($res, 200);
    }

    /**
     * @OA\Post(
     *     path="/shopping/items",
     *     summary="買い物アイテムを作成",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemStoreRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        if (!$request->categoryId || !$request->name) {
            return response()->json(['message' => '無効なデータ形式です。'], 400);
        }

        $ret = ShoppingItem::create([
            'group_id' => $group->id,
            'category_id' => $request->categoryId,
            'name' => $request->name,
            'is_pinned' => false,
            'is_checked' => false,
            'order' => $group->shoppingItems->where('category_id', $request->categoryId)->count()
        ]);

        return response()->json([
            'id' => $ret->id,
            'categoryId' => $ret->category_id,
            'name' => $ret->name,
            'isPinned' => $ret->is_pinned,
            'isChecked' => $ret->is_checked,
            'order' => $ret->order
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/shopping/items/bulk",
     *     summary="買い物アイテムを一括更新",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        foreach ($request->items as $item) {
            ShoppingItem::where('id', $item['id'])->update([
                'category_id' => $item['categoryId'],
                'name' => $item['name'],
                'is_pinned' => $item['isPinned'],
                'is_checked' => $item['isChecked'],
                'order' => $item['order']
            ]);
        }

        $ret = $group->shoppingItems()->select('id', 'category_id as categoryId', 'name', 'is_pinned as isPinned', 'is_checked as isChecked', 'order')->get();

        return response()->json($ret, 200);
    }

    /**
     * @OA\Delete(
     *     path="/shopping/items/bulk",
     *     summary="買い物アイテムを一括削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        Log::info('bulkDestroy', ['ids' => $request->itemIds]);
        $deletedIds = [];
        foreach ($request->itemIds as $id) {
            $item = ShoppingItem::where('id', $id)->first();

            if (!$item) {
                Log::info('指定されたレコードが見つかりません。', ['function' => 'ShoppingItemController@bulkDestroy', 'id' => $id]);
                return response()->json([
                    'message' => '指定されたレコードが見つかりません。'
                ], 404);
            }

            $deletedIds[] = [$item->id];
            $item->delete();
        }
        return response()->json(['ids' => $deletedIds], 200);
    }
}
