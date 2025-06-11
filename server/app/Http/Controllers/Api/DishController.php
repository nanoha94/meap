<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Seasoning;
use App\Models\DishCategory;
use App\Traits\AutoComplement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DishController extends Controller
{
    use AutoComplement;

    /**
     * @OA\Get(
     *     path="/dishes",
     *     summary="料理一覧を取得",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishPageParam"),
     *     @OA\Parameter(ref="#/components/parameters/DishPerPageParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        // ページネーションのパラメータを取得（デフォルト値も設定）
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        // TODO: 無限スクロール対応

        $dishes = $group->dishes()->select('id', 'name', 'thumbnail_url', 'url', 'recipe', 'memo')->with(['categories', 'seasonings', 'ingredients'])->get();
        $res = [
            'dishes' => $dishes->map(function ($dish) {
                return [
                    'id' => $dish->id,
                    'name' => $dish->name,
                    'thumbnailUrl' => $dish->thumbnail_url,
                    'url' => $dish->url,
                    'recipe' => $dish->recipe,
                    'memo' => $dish->memo,
                    'categories' => $dish->categories->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name
                    ]),
                    'seasonings' => $dish->seasonings->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id
                    ]),
                    'ingredients' => $dish->ingredients->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id
                    ])
                ];
            }),
            'total' => $dishes->count()
        ];

        return response()->json($res, 200);
    }

    /**
     * @OA\Post(
     *     path="/dishes",
     *     summary="料理を作成",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/DishRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = Dish::create([
            'group_id' => $group->id,
            'name' => $request->name,
            'thumbnail_url' => $request->thumbnailUrl,
            'url' => $request->url,
            'recipe' => $request->recipe,
            'memo' => $request->memo,
        ]);

        // カテゴリーを紐づけ
        if (!empty($request->categories)) {
            $categoryIds = collect($request->categories)->pluck('id')->toArray();
            $existingCategoryIds = DishCategory::whereIn('id', $categoryIds)
                ->pluck('id')
                ->toArray();

            $ret->categories()->attach($existingCategoryIds);
        }

        // 調味料を紐づけ
        if (!empty($request->seasonings)) {
            $ids = $this->findOrCreateIds(
                collect($request->seasonings)->map(fn($item) => [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name']
                ])->toArray(),
                $group,
                Seasoning::class
            );
            $data = collect($request->seasonings)->mapWithKeys(function ($item, $idx) use ($ids) {
                return [$ids[$idx] => [
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unitId']
                ]];
            })->toArray();
            $ret->seasonings()->attach($data);
        }

        // 食材を紐づけ
        if (!empty($request->ingredients)) {
            $ids = $this->findOrCreateIds(
                collect($request->ingredients)->map(fn($item) => [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name']
                ])->toArray(),
                $group,
                Ingredient::class
            );
            $data = collect($request->ingredients)->mapWithKeys(function ($item, $idx) use ($ids) {
                return [$ids[$idx] => [
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unitId']
                ]];
            })->toArray();
            $ret->ingredients()->attach($data);
        }

        return response()->json([
            'id' => $ret->id,
            'name' => $ret->name,
            'thumbnailUrl' => $ret->thumbnail_url,
            'url' => $ret->url,
            'recipe' => $ret->recipe,
            'memo' => $ret->memo,
            'categories' => $ret->categories->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name
            ]),
            'seasonings' => $ret->seasonings->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
            'ingredients' => $ret->ingredients->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/dishes/{id}",
     *     summary="料理の詳細を取得",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $dish = Dish::where('id', $id)->where('group_id', $group->id)->with(['categories', 'seasonings', 'ingredients'])->first();
        if (!$dish) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $res = [
            'id' => $dish->id,
            'name' => $dish->name,
            'thumbnailUrl' => $dish->thumbnail_url,
            'url' => $dish->url,
            'recipe' => $dish->recipe,
            'memo' => $dish->memo,
            'categories' => $dish->categories->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name
            ]),
            'seasonings' => $dish->seasonings->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
            'ingredients' => $dish->ingredients->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ])
        ];

        return response()->json($res, 200);
    }

    /**
     * @OA\Put(
     *     path="/dishes/{id}",
     *     summary="料理を更新",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/DishRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $dish =  Dish::where('id', $id)->where('group_id', $group->id)->first();
        if (!$dish) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $dish->update([
            'name' => $request->name,
            'thumbnail_url' => $request->thumbnailUrl,
            'url' => $request->url,
            'recipe' => $request->recipe,
            'memo' => $request->memo,
        ]);

        // カテゴリー更新
        if (!empty($request->categories)) {
            $categoryIds = collect($request->categories)->pluck('id')->toArray();
            $existingCategoryIds = DishCategory::whereIn('id', $categoryIds)
                ->pluck('id')
                ->toArray();

            $dish->categories()->sync($existingCategoryIds);
        }

        // 調味料更新
        if (!empty($request->seasonings)) {
            $ids = $this->findOrCreateIds(
                collect($request->seasonings)->map(fn($item) => [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name']
                ])->toArray(),
                $group,
                Seasoning::class
            );
            $data = collect($request->seasonings)->mapWithKeys(function ($item, $idx) use ($ids) {
                return [$ids[$idx] => [
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unitId']
                ]];
            })->toArray();
            $dish->seasonings()->sync($data);
        }

        // 食材更新
        if (!empty($request->ingredients)) {
            $ids = $this->findOrCreateIds(
                collect($request->ingredients)->map(fn($item) => [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name']
                ])->toArray(),
                $group,
                Ingredient::class
            );
            $data = collect($request->ingredients)->mapWithKeys(function ($item, $idx) use ($ids) {
                return [$ids[$idx] => [
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unitId']
                ]];
            })->toArray();
            $dish->ingredients()->sync($data);
        }


        $updatedItem = $group->dishes()->where('id', $id)->first();

        return response()->json([
            'id' => $updatedItem->id,
            'name' => $updatedItem->name,
            'thumbnailUrl' => $updatedItem->thumbnail_url,
            'url' => $updatedItem->url,
            'recipe' => $updatedItem->recipe,
            'memo' => $updatedItem->memo,
            'categories' => $updatedItem->categories->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name
            ]),
            'seasonings' => $updatedItem->seasonings->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
            'ingredients' => $updatedItem->ingredients->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/dishes/{id}",
     *     summary="料理を削除",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $dish =  Dish::where('id', $id)->where('group_id', $group->id)->first();

        if (!$dish) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $deletedId = $dish->id;
        $dish->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
