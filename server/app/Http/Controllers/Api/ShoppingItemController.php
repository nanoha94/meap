<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use App\Models\ShoppingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $res = [];
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        $categories = ShoppingCategory::where('group_id', $groupId)->get();

        foreach ($categories as $category) {
            $items = ShoppingItem::where('group_id', $groupId)->where('category_id', $category->id)->get();
            if ($items->count() > 0) {
                foreach ($items as $idx => $item) {
                    $res[$idx] = ['id' => $item->id, 'name' => $item->name, 'isPinned' => (bool)$item->is_pinned, 'isChecked' => (bool)$item->is_checked, 'categoryId' => $category->id, 'order' => $item->order];
                }
            }
        }

        return response()->json($res);
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        ShoppingItem::upsert([
            'id' => $request->id,
            'group_id' => $groupId,
            'category_id' => $request->categoryId,
            'name' => $request->name,
            'is_pinned' => $request->isPinned,
            'is_checked' => $request->isChecked,
            'order' => $request->order,
        ], uniqueBy: ['id'], update: ['name', 'category_id', 'is_pinned', 'is_checked', 'order']);

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
