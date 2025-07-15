<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Seasoning;
use App\Models\RecipeCategory;
use App\Traits\AutoComplement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    use AutoComplement;

    /**
     * @OA\Get(
     *     path="/recipes",
     *     summary="料理一覧を取得",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipePageParam"),
     *     @OA\Parameter(ref="#/components/parameters/RecipePerPageParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeIndexSuccess"),
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

        $recipes = $group->recipes()->select('id', 'name', 'thumbnail_url', 'url', 'recipe', 'memo')->with(['categories', 'seasonings', 'ingredients'])->get();
        $res = [
            'recipes' => $recipes->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'thumbnailUrl' => $recipe->thumbnail_url,
                    'url' => $recipe->url,
                    'recipe' => $recipe->recipe,
                    'memo' => $recipe->memo,
                    'categories' => $recipe->categories->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name
                    ]),
                    'seasonings' => $recipe->seasonings->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id
                    ]),
                    'ingredients' => $recipe->ingredients->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id
                    ])
                ];
            }),
            'total' => $recipes->count()
        ];

        return response()->json($res, 200);
    }

    /**
     * @OA\Post(
     *     path="/recipes",
     *     summary="料理を作成",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = Recipe::create([
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
            $existingCategoryIds = RecipeCategory::whereIn('id', $categoryIds)
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
     *     path="/recipes/{id}",
     *     summary="料理の詳細を取得",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $recipe = Recipe::where('id', $id)->where('group_id', $group->id)->with(['categories', 'seasonings', 'ingredients'])->first();
        if (!$recipe) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $res = [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'thumbnailUrl' => $recipe->thumbnail_url,
            'url' => $recipe->url,
            'recipe' => $recipe->recipe,
            'memo' => $recipe->memo,
            'categories' => $recipe->categories->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name
            ]),
            'seasonings' => $recipe->seasonings->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id
            ]),
            'ingredients' => $recipe->ingredients->map(fn($item) => [
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
     *     path="/recipes/{id}",
     *     summary="料理を更新",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $recipe =  Recipe::where('id', $id)->where('group_id', $group->id)->first();
        if (!$recipe) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $recipe->update([
            'name' => $request->name,
            'thumbnail_url' => $request->thumbnailUrl,
            'url' => $request->url,
            'recipe' => $request->recipe,
            'memo' => $request->memo,
        ]);

        // カテゴリー更新
        if (!empty($request->categories)) {
            $categoryIds = collect($request->categories)->pluck('id')->toArray();
            $existingCategoryIds = RecipeCategory::whereIn('id', $categoryIds)
                ->pluck('id')
                ->toArray();

            $recipe->categories()->sync($existingCategoryIds);
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
            $recipe->seasonings()->sync($data);
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
            $recipe->ingredients()->sync($data);
        }


        $updatedItem = $group->recipes()->where('id', $id)->first();

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
     *     path="/recipes/{id}",
     *     summary="料理を削除",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $recipe =  Recipe::where('id', $id)->where('group_id', $group->id)->first();

        if (!$recipe) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $deletedId = $recipe->id;
        $recipe->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
