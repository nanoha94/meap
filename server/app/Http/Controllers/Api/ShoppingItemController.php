<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use App\Models\ShoppingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShoppingItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $res = [];
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        $items = ShoppingItem::where('group_id', $groupId)->get();
        if ($items->count() > 0) {
            foreach ($items as $idx => $item) {
                $res[$idx] = ['id' => $item->id, 'name' => $item->name, 'isPinned' => (bool)$item->is_pinned, 'isChecked' => (bool)$item->is_checked, 'categoryId' => $item->category_id, 'order' => $item->order];
            }
        }

        return response()->json($res);
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        $request_items = $request->items;
        Log::info('request_items', ['request_items' => $request->all()]);

        if (!is_array($request_items) || empty($request_items)) {
            return response()->json(['message' => '無効なデータ形式です'], 400);
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

        return response()->json(['message' => '買い物リストを更新しました']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $item =  ShoppingItem::where('id', $request->id)->first();
        $item_name = $item->name;
        $item->delete();
        return response()->json(['message' => $item_name . 'を買い物リストから削除しました']);
    }
}
