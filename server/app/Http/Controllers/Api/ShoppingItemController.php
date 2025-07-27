<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingItem;
use App\Models\ShoppingTag;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Support\Facades\DB;

class ShoppingItemController extends Controller
{
    use AutoComplement;

    /**
     * @OA\Get(
     *     path="/shopping-items",
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
        try {
            $user = $request->user();
            $group = $user->group;

            $res = [];
            $categories = $group->shoppingCategories()
                ->select('id', 'name', 'is_default', 'order')
                ->orderBy('order', 'asc')
                ->get();

            foreach ($categories as $category) {
                $items = $category->shoppingItems()
                    ->select('id', 'name', 'is_pinned', 'is_checked', 'category_id', 'order')
                    ->with('tags:id,name')
                    ->orderBy('order', 'asc')
                    ->get();

                $res[] = [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'isDefault' => (bool)$category->is_default,
                        'order' => $category->order
                    ],
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'isPinned' => (bool)$item->is_pinned,
                            'isChecked' => (bool)$item->is_checked,
                            'categoryId' => $item->category_id,
                            'tags' => $item->tags->map(function ($tag) {
                                return [
                                    'id' => $tag->id,
                                    'name' => $tag->name
                                ];
                            }),
                            'order' => $item->order
                        ];
                    })
                ];
            }

            return response()->json([
                'data' => $res
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => '買い物アイテムの取得中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/shopping-items",
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
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
            $request->validate([
                'name' => 'required|string|max:255',
                'categoryId' => 'required|string|max:255',
                'tags' => 'nullable|array',
                'tags.*.id' => 'nullable|string|max:255',
                'tags.*.name' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            $ret = ShoppingItem::create([
                'group_id' => $group->id,
                'category_id' => $request->categoryId,
                'name' => $request->name,
                'is_pinned' => false,
                'is_checked' => false,
                'order' => $group->shoppingItems->where('category_id', $request->categoryId)->count()
            ]);
            if (!$ret) {
                DB::rollBack();
                return response()->json([
                    'message' => '買い物アイテムの作成に失敗しました。'
                ], 500);
            }

            // タグの処理
            if (!empty($request->tags)) {
                try {
                    $tagIds = $this->findOrCreateIds($request->tags, $group, ShoppingTag::class);
                    if (empty($tagIds)) {
                        Log::warning('タグの作成に失敗しました。', [
                            'function' => 'ShoppingItemController@store',
                            'tags' => $request->tags
                        ]);
                    } else {
                        $ret->tags()->attach(array_values($tagIds));
                    }
                } catch (Exception $e) {
                    Log::error('タグの処理中にエラーが発生しました。', [
                        'function' => 'ShoppingItemController@store',
                        'error' => $e->getMessage(),
                        'tags' => $request->tags
                    ]);
                    // タグ処理でエラーが発生しても、アイテム作成は成功させる
                }
            }

            DB::commit();

            // タグを含めて再取得
            $ret = $ret->fresh(['tags:id,name']);

            return response()->json([
                'id' => $ret->id,
                'categoryId' => $ret->category_id,
                'name' => $ret->name,
                'isPinned' => $ret->is_pinned,
                'isChecked' => $ret->is_checked,
                'order' => $ret->order,
                'tags' => $ret->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name
                    ];
                }),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('買い物アイテムの作成中にエラーが発生しました。', [
                'function' => 'ShoppingItemController@store',
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => '買い物アイテムの作成中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/shopping-items/bulk",
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
        try {
            $user = $request->user();
            $group = $user->group;

            DB::beginTransaction();

            $updatedItems = [];
            foreach ($request->data as $item) {
                $shoppingItem = ShoppingItem::where('id', $item['id'])->first();
                if (!$shoppingItem) {
                    continue;
                }

                // 基本情報の更新
                $shoppingItem->update([
                    'category_id' => $item['categoryId'],
                    'name' => $item['name'],
                    'is_pinned' => $item['isPinned'],
                    'is_checked' => $item['isChecked'],
                    'order' => $item['order']
                ]);

                // タグの更新
                if (!empty($item['tags'])) {
                    try {
                        $tagIds = $this->findOrCreateIds($item['tags'], $group, ShoppingTag::class);
                        if (empty($tagIds)) {
                            Log::warning('タグの作成に失敗しました。', [
                                'function' => 'ShoppingItemController@bulkUpdate',
                                'item_id' => $item['id'],
                                'tags' => $item['tags']
                            ]);
                        } else {
                            $shoppingItem->tags()->sync(array_values($tagIds));
                        }
                    } catch (Exception $e) {
                        Log::error('タグの処理中にエラーが発生しました。', [
                            'function' => 'ShoppingItemController@bulkUpdate',
                            'item_id' => $item['id'],
                            'error' => $e->getMessage(),
                            'tags' => $item['tags']
                        ]);
                        // タグ処理でエラーが発生しても、アイテム更新は成功させる
                    }
                }

                $updatedItems[] = $shoppingItem;
            }

            DB::commit();

            // 更新したアイテムのみを取得
            $ret = collect($updatedItems)
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'categoryId' => $item->category_id,
                        'name' => $item->name,
                        'isPinned' => $item->is_pinned,
                        'isChecked' => $item->is_checked,
                        'order' => $item->order,
                        'tags' => $item->tags->map(function ($tag) {
                            return [
                                'id' => $tag->id,
                                'name' => $tag->name
                            ];
                        })
                    ];
                });

            return response()->json($ret, 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('買い物アイテムの一括更新中にエラーが発生しました。', [
                'function' => 'ShoppingItemController@bulkUpdate',
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => '買い物アイテムの一括更新中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/shopping-items/bulk",
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
        try {
            $user = $request->user();
            $group = $user->group;

            // IDの配列を取得（単一IDの場合も配列として扱う）
            $ids = $request->ids;

            // 空の場合はエラー
            if (empty($ids)) {
                return response()->json([
                    'message' => '削除するアイテムのIDを指定してください。'
                ], 400);
            }

            $deletedIds = [];
            foreach ($ids as $id) {
                $item = $group->shoppingItems()->where('id', $id)->first();

                if (!$item) {
                    Log::error('指定されたレコードが見つかりません。', [
                        'function' => 'ShoppingItemController@bulkDestroy',
                        'id' => $id
                    ]);
                    return response()->json([
                        'message' => '指定されたレコードが見つかりません。'
                    ], 404);
                }

                $deletedIds[] = $item->id;
                $item->delete();
            }

            return response()->json(['ids' => $deletedIds], 200);
        } catch (Exception $e) {
            Log::error('買い物アイテム削除エラー: ' . $e->getMessage());
            return response()->json([
                'message' => '削除処理中にエラーが発生しました。'
            ], 500);
        }
    }
}
