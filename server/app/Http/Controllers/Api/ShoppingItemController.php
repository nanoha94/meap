<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingItem;
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
        $res = [];
        $user = $request->user();
        $groupId = $user->group_id;

        $items = ShoppingItem::where('group_id', $groupId)->get();
        if ($items->count() > 0) {
            foreach ($items as $idx => $item) {
                $res[$idx] = ['id' => $item->id, 'name' => $item->name, 'isPinned' => (bool)$item->is_pinned, 'isChecked' => (bool)$item->is_checked, 'categoryId' => $item->category_id, 'order' => $item->order];
            }
        }

        return response()->json($res);
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
        $groupId = $user->group_id;

        $request_items = $request->items;

        if (!is_array($request_items) || empty($request_items)) {
            return response()->json(['message' => '無効なデータ形式です。'], 400);
        }

        $items = [];
        foreach ($request->items as $item) {
            $items[] = [
                'id' => $item['id'],
                'group_id' => $groupId,
                'category_id' => $item['categoryId'],
                'name' => $item['name'],
                'is_pinned' => $item['isPinned'],
                'is_checked' => $item['isChecked'],
                'order' => $item['order'],
            ];
        }

        ShoppingItem::upsert($items, uniqueBy: ['id'], update: ['name', 'category_id', 'is_pinned', 'is_checked', 'order']);

        return response()->json(['message' => '買い物リストを更新しました。']);
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
    public function bulkUpdate(Request $request)
    {
        //
    }

    /**
     * @OA\Delete(
     *     path="/shopping/items/{id}",
     *     summary="買い物アイテムを削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/ShoppingIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $item =  ShoppingItem::where('id', $id)->first();
        $item_name = $item->name;
        $item->delete();
        return response()->json(['message' => $item_name . 'を買い物リストから削除しました。']);
    }

    /**
     * @OA\Delete(
     *     path="/shopping/items/bulk",
     *     summary="買い物アイテムを一括削除",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ShoppingItemBulkDeleteRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingItemDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->group_id;
        ShoppingItem::where('group_id', $groupId)->where('is_pinned', false | 0)->delete();
        return response()->json(['message' => '買い物アイテムをすべて削除しました。']);
    }
}
