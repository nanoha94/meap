<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Seasoning;
use App\Models\RecipeCategory;
use App\Services\ImageService;
use App\Traits\AutoComplement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    use AutoComplement;

    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

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

        $recipes = $group->recipes()->select('id', 'name', 'thumbnail_url', 'thumbnail_width', 'thumbnail_height', 'url', 'instructions', 'memo')->with(['categories', 'seasonings', 'ingredients'])->get();
        $res = [
            'data' => $recipes->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'thumbnail' => $recipe->thumbnail_url ? [
                        'url' => $recipe->thumbnail_url,
                        'width' => $recipe->thumbnail_width,
                        'height' => $recipe->thumbnail_height,
                    ] : null,
                    'url' => $recipe->url,
                    'instructions' => $recipe->instructions,
                    'memo' => $recipe->memo,
                    'categories' => $recipe->categories->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'order' => $item->order
                    ]),
                    'seasonings' => $recipe->seasonings->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id,
                        'order' => $item->pivot->order
                    ]),
                    'ingredients' => $recipe->ingredients->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unitId' => $item->pivot->unit_id,
                        'order' => $item->pivot->order
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

        // リクエストデータのバリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'recipe' => 'nullable|string',
            'memo' => 'nullable|string',
        ], [
            'name.required' => 'レシピ名を入力してください。',
            'name.string' => 'レシピ名は文字列で入力してください。',
            'name.max' => 'レシピ名は255文字以内で入力してください。',
            'url.string' => 'URLは文字列で入力してください。',
            'url.max' => 'URLは2048文字以内で入力してください。',
            'recipe.string' => 'レシピ内容は文字列で入力してください。',
            'memo.string' => 'メモは文字列で入力してください。',
        ]);

        // 画像ファイルの検証（アップロードする場合のみ）
        if ($request->hasFile('thumbnailImage')) {
            $request->validate([
                'thumbnailImage' => $this->imageService->getValidationRules()
            ]);
        }

        try {
            $ret = DB::transaction(function () use ($request, $group) {
                // 1. データベース処理を先に実行（画像情報は後で設定）
                $ret = Recipe::create([
                    'group_id' => $group->id,
                    'name' => $request->name,
                    'thumbnail_url' => null, // 後で設定
                    'thumbnail_width' => null, // 後で設定
                    'thumbnail_height' => null, // 後で設定
                    'url' => $request->url,
                    'recipe' => $request->recipe,
                    'memo' => $request->memo,
                ]);

                // 2. カテゴリーを紐づけ
                if (!empty($request->categoryIds)) {
                    $categoryIds = $request->categoryIds;

                    // もし配列で来ていればそのまま
                    if (is_array($categoryIds)) {
                        // 何もしない
                    }
                    // もしJSON配列文字列ならdecode
                    else if (is_string($categoryIds) && preg_match('/^\[.*\]$/', $categoryIds)) {
                        $categoryIds = json_decode($categoryIds, true);
                    }
                    // それ以外（単一IDの文字列）は配列にラップ
                    else if (is_string($categoryIds)) {
                        $categoryIds = [$categoryIds];
                    }

                    $existingCategoryIds = RecipeCategory::whereIn('id', $categoryIds)
                        ->pluck('id')
                        ->toArray();

                    $ret->categories()->attach($existingCategoryIds);
                }

                // 3. 調味料を紐づけ
                if (!empty($request->seasonings)) {
                    $seasonings = is_string($request->seasonings)
                        ? json_decode($request->seasonings, true)
                        : $request->seasonings;

                    // JSON パースエラーチェック
                    if (is_string($request->seasonings) && json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('JSON decode error for seasonings:', ['error' => json_last_error_msg()]);
                        $seasonings = [];
                    }

                    // 連想配列（単一オブジェクト）の場合は配列でラップ
                    if (!empty($seasonings) && is_array($seasonings) && !array_key_exists(0, $seasonings)) {
                        $seasonings = [$seasonings];
                    }

                    // 空でないかつ配列の場合のみ処理
                    if (!empty($seasonings) && is_array($seasonings)) {
                        $ids = $this->findOrCreateIds(
                            collect($seasonings)->map(fn($item) => [
                                'id' => $item['id'] ?? null,
                                'name' => $item['name']
                            ])->toArray(),
                            $group,
                            Seasoning::class
                        );
                        $data = collect($seasonings)->mapWithKeys(function ($item, $idx) use ($ids) {
                            return [$ids[$idx] => [
                                'quantity' => $item['quantity'],
                                'unit_id' => $item['unitId'],
                                'order' => $item['order'] ?? 0
                            ]];
                        })->toArray();
                        $ret->seasonings()->attach($data);
                    }
                }

                // 4. 食材を紐づけ
                if (!empty($request->ingredients)) {
                    $ingredients = is_string($request->ingredients)
                        ? json_decode($request->ingredients, true)
                        : $request->ingredients;

                    // 連想配列（単一オブジェクト）の場合は配列でラップ
                    if (!empty($ingredients) && is_array($ingredients) && !array_key_exists(0, $ingredients)) {
                        $ingredients = [$ingredients];
                    }

                    $ids = $this->findOrCreateIds(
                        collect($ingredients)->map(fn($item) => [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'],

                        ])->toArray(),
                        $group,
                        Ingredient::class
                    );
                    $data = collect($ingredients)->mapWithKeys(function ($item, $idx) use ($ids) {
                        return [$ids[$idx] => [
                            'quantity' => $item['quantity'],
                            'unit_id' => $item['unitId'],
                            'order' => $item['order'] ?? 0
                        ]];
                    })->toArray();
                    $ret->ingredients()->attach($data);
                }

                return $ret;
            });

            // 5. データベース処理が成功した場合のみ画像処理を実行
            $thumbnail_url = null;
            $thumbnail_width = null;
            $thumbnail_height = null;

            // 画像ファイルが存在する場合はアップロード
            if ($request->hasFile('thumbnailImage')) {
                $imageData = $this->imageService->uploadImage(
                    $request->file('thumbnailImage'),
                    "$group->id/recipes/thumbnails"
                );

                $thumbnail_url = $imageData['url'];
                $thumbnail_width = $imageData['width'];
                $thumbnail_height = $imageData['height'];

                // 6. 画像情報をデータベースに反映
                $ret->update([
                    'thumbnail_url' => $thumbnail_url,
                    'thumbnail_width' => $thumbnail_width,
                    'thumbnail_height' => $thumbnail_height,
                ]);
            }

            return response()->json([
                'id' => $ret->id,
                'name' => $ret->name,
                'thumbnail' => $ret->thumbnail_url ? [
                    'url' => $ret->thumbnail_url,
                    'width' => $ret->thumbnail_width,
                    'height' => $ret->thumbnail_height,
                ] : null,
                'url' => $ret->url,
                'recipe' => $ret->recipe,
                'memo' => $ret->memo,
                'categories' => $ret->categories->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'order' => $item->order
                ]),
                'seasonings' => $ret->seasonings->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'unitId' => $item->pivot->unit_id,
                    'order' => $item->pivot->order
                ]),
                'ingredients' => $ret->ingredients->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'unitId' => $item->pivot->unit_id,
                    'order' => $item->pivot->order
                ]),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Recipe creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'レシピの作成に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
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
            'thumbnail' => $recipe->thumbnail_url ? [
                'url' => $recipe->thumbnail_url,
                'width' => $recipe->thumbnail_width,
                'height' => $recipe->thumbnail_height,
            ] : null,
            'url' => $recipe->url,
            'recipe' => $recipe->recipe,
            'memo' => $recipe->memo,
            'categories' => $recipe->categories->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'order' => $item->order
            ]),
            'seasonings' => $recipe->seasonings->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id,
                'order' => $item->pivot->order
            ]),
            'ingredients' => $recipe->ingredients->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id,
                'order' => $item->pivot->order
            ])
        ];

        return response()->json($res, 200);
    }

    /**
     * @OA\Post(
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

        // リクエストデータのバリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'recipe' => 'nullable|string',
            'memo' => 'nullable|string',
        ], [
            'name.required' => 'レシピ名を入力してください。',
            'name.string' => 'レシピ名は文字列で入力してください。',
            'name.max' => 'レシピ名は255文字以内で入力してください。',
            'url.string' => 'URLは文字列で入力してください。',
            'url.max' => 'URLは2048文字以内で入力してください。',
            'recipe.string' => 'レシピ内容は文字列で入力してください。',
            'memo.string' => 'メモは文字列で入力してください。',
        ]);

        // 画像ファイルの検証（アップロードする場合のみ）
        if ($request->hasFile('thumbnailImage')) {
            $request->validate([
                'thumbnailImage' => $this->imageService->getValidationRules()
            ]);
        }

        try {
            $result = DB::transaction(function () use ($request, $recipe, $group) {
                // 1. データベース更新処理を先に実行
                $recipe->update([
                    'name' => $request->name,
                    'url' => $request->url,
                    'instructions' => $request->instructions,
                    'memo' => $request->memo,
                ]);

                // 2. カテゴリー更新
                if (!empty($request->categoryIds)) {
                    $categoryIds = $request->categoryIds;
                    // もし配列で来ていればそのまま
                    if (is_array($categoryIds)) {
                        // 何もしない
                    }
                    // もしJSON配列文字列ならdecode
                    else if (is_string($categoryIds) && preg_match('/^\[.*\]$/', $categoryIds)) {
                        $categoryIds = json_decode($categoryIds, true);
                    }
                    // それ以外（単一IDの文字列）は配列にラップ
                    else if (is_string($categoryIds)) {
                        $categoryIds = [$categoryIds];
                    }

                    $existingCategoryIds = RecipeCategory::whereIn('id', $categoryIds)
                        ->pluck('id')
                        ->toArray();

                    $recipe->categories()->sync($existingCategoryIds);
                }

                // 3. 調味料更新
                if (!empty($request->seasonings)) {
                    $seasonings = is_string($request->seasonings)
                        ? json_decode($request->seasonings, true)
                        : $request->seasonings;

                    // JSON パースエラーチェック
                    if (is_string($request->seasonings) && json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('JSON decode error for seasonings:', ['error' => json_last_error_msg()]);
                        $seasonings = [];
                    }

                    // 連想配列（単一オブジェクト）の場合は配列でラップ
                    if (!empty($seasonings) && is_array($seasonings) && !array_key_exists(0, $seasonings)) {
                        $seasonings = [$seasonings];
                    }

                    // 空でないかつ配列の場合のみ処理
                    if (!empty($seasonings) && is_array($seasonings)) {
                        $ids = $this->findOrCreateIds(
                            collect($seasonings)->map(fn($item) => [
                                'id' => $item['id'] ?? null,
                                'name' => $item['name']
                            ])->toArray(),
                            $group,
                            Seasoning::class
                        );
                        $data = collect($seasonings)->mapWithKeys(function ($item, $idx) use ($ids) {
                            return [$ids[$idx] => [
                                'quantity' => $item['quantity'],
                                'unit_id' => $item['unitId'],
                                'order' => $item['order'] ?? 0
                            ]];
                        })->toArray();
                        $recipe->seasonings()->sync($data);
                    }
                }

                // 4. 食材更新
                if (!empty($request->ingredients)) {
                    $ingredients = is_string($request->ingredients)
                        ? json_decode($request->ingredients, true)
                        : $request->ingredients;

                    // 連想配列（単一オブジェクト）の場合は配列でラップ
                    if (!empty($ingredients) && is_array($ingredients) && !array_key_exists(0, $ingredients)) {
                        $ingredients = [$ingredients];
                    }

                    $ids = $this->findOrCreateIds(
                        collect($ingredients)->map(fn($item) => [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name']
                        ])->toArray(),
                        $group,
                        Ingredient::class
                    );
                    $data = collect($ingredients)->mapWithKeys(function ($item, $idx) use ($ids) {
                        return [$ids[$idx] => [
                            'quantity' => $item['quantity'],
                            'unit_id' => $item['unitId'],
                            'order' => $item['order'] ?? 0
                        ]];
                    })->toArray();
                    $recipe->ingredients()->sync($data);
                }

                return true;
            });

            // 5. データベース処理が成功した場合のみ画像処理を実行
            $thumbnail_url = $recipe->thumbnail_url; // 既存のURLを保持
            $thumbnail_width = $recipe->thumbnail_width; // 既存のwidthを保持
            $thumbnail_height = $recipe->thumbnail_height; // 既存のheightを保持

            // すでに画像が存在している場合
            if ($recipe->thumbnail_url) {
                // リクエストボディに画像ファイルが存在する場合はアップロード
                if ($request->hasFile('thumbnailImage')) {
                    $imageData = $this->imageService->uploadImage(
                        $request->file('thumbnailImage'),
                        "$group->id/recipes/thumbnails",
                        $recipe->thumbnail_url
                    );

                    $thumbnail_url = $imageData['url'];
                    $thumbnail_width = $imageData['width'];
                    $thumbnail_height = $imageData['height'];
                }
                // リクエストボディに画像ファイルが存在しない場合は削除
                else {
                    // 既存画像を削除
                    $this->imageService->deleteImage($recipe->thumbnail_url);
                    $thumbnail_url = null;
                    $thumbnail_width = null;
                    $thumbnail_height = null;
                }
            }
            // 画像が存在しない場合で、新しい画像がアップロードされた場合
            else if ($request->hasFile('thumbnailImage')) {
                $imageData = $this->imageService->uploadImage(
                    $request->file('thumbnailImage'),
                    "$group->id/recipes/thumbnails"
                );

                $thumbnail_url = $imageData['url'];
                $thumbnail_width = $imageData['width'];
                $thumbnail_height = $imageData['height'];
            }

            // 6. 画像情報をデータベースに反映
            $recipe->update([
                'thumbnail_url' => $thumbnail_url,
                'thumbnail_width' => $thumbnail_width,
                'thumbnail_height' => $thumbnail_height,
            ]);

            $updatedItem = $group->recipes()->where('id', $id)->first();

            return response()->json([
                'id' => $updatedItem->id,
                'name' => $updatedItem->name,
                'thumbnail' => $updatedItem->thumbnail_url ? [
                    'url' => $updatedItem->thumbnail_url,
                    'width' => $updatedItem->thumbnail_width,
                    'height' => $updatedItem->thumbnail_height,
                ] : null,
                'url' => $updatedItem->url,
                'recipe' => $updatedItem->recipe,
                'memo' => $updatedItem->memo,
                'categories' => $updatedItem->categories->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'order' => $item->order
                ]),
                'seasonings' => $updatedItem->seasonings->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'unitId' => $item->pivot->unit_id,
                    'order' => $item->pivot->order
                ]),
                'ingredients' => $updatedItem->ingredients->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'unitId' => $item->pivot->unit_id,
                    'order' => $item->pivot->order
                ]),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Recipe update failed:', [
                'recipe_id' => $recipe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'レシピの更新に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
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

        // 画像ファイルを削除
        $this->imageService->deleteImage($recipe->thumbnail_url);

        $recipe->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
