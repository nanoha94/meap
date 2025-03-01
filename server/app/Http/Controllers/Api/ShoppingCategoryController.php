<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $res = [];
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        $categories = ShoppingCategory::where('group_id', $groupId)->get();

        foreach ($categories as $idx => $category) {
            $res[$idx] = ['id' => $category->id, 'name' => $category->name, 'isDefault' => (bool)$category->is_default, 'order' => $category->order];
        }

        return response()->json($res);
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        ShoppingCategory::upsert([
            'id' => $request->id,
            'group_id' => $groupId,
            'name' => $request->name,
            'is_default' => false,
            'order' => $request->order,
        ], uniqueBy: ['id'], update: ['name', 'order']);

        return response()->json(['message' => '買い物カテゴリーを更新しました。']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $category =  ShoppingCategory::where('id', $request->id)->first();
        $category_name = $category->name;
        $category->delete();
        return response()->json(['message' => $category_name . 'を買い物カテゴリーから削除しました。']);
    }
}
