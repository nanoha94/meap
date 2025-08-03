<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\RecipeCategory;
use App\Models\Group;
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

        // TODO: 無限スクロール対応？
        $recipes = $group->recipes()->select('id', 'name', 'thumbnail_url', 'thumbnail_width', 'thumbnail_height', 'url', 'memo')->with(['categories', 'ingredients', 'steps'])->get();
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
                    'steps' => $recipe->steps->map(fn($item) => [
                        'id' => $item->id,
                        'instruction' => $item->instruction,
                        'image' => $item->image_url ? [
                            'url' => $item->image_url,
                            'width' => $item->image_width,
                            'height' => $item->image_height,
                        ] : null,
                        'order' => $item->order,
                    ]),
                    'memo' => $recipe->memo,
                    'categories' => $recipe->categories->sortBy('order')->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
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
        $this->validateRecipeRequest($request);

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
                    'steps' => null, // 後で設定
                    'memo' => $request->memo,
                ]);

                // 2. カテゴリーを紐づけ
                $this->syncCategories($ret, $request->categoryIds, false);

                // 3. 食材を紐づけ
                $this->syncIngredients($ret, $request->ingredients, false, $group);

                return $ret;
            });

            // 5. データベース処理が成功した場合のみ画像処理を実行
            $thumbnail_url = null;
            $thumbnail_width = null;
            $thumbnail_height = null;

            // 画像ファイルが存在する場合はアップロード
            if ($request->hasFile('thumbnail')) {
                $imageData = $this->imageService->uploadImage(
                    $request->file('thumbnail'),
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

            return response()->json($this->formatRecipeResponse($ret), 200);
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

        $recipe = Recipe::where('id', $id)->where('group_id', $group->id)->with(['categories', 'ingredients'])->first();
        if (!$recipe) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        return response()->json($this->formatRecipeResponse($recipe), 200);
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
        $this->validateRecipeRequest($request);

        try {
            DB::transaction(function () use ($request, $recipe, $group) {
                // 1. データベース更新処理を先に実行
                $recipe->update([
                    'name' => $request->name,
                    'url' => $request->url,
                    'steps' => $request->steps,
                    'memo' => $request->memo,
                ]);

                // 2. カテゴリー更新
                $this->syncCategories($recipe, $request->categoryIds, true);

                // 3. 食材更新
                $this->syncIngredients($recipe, $request->ingredients, true, $group);

                return true;
            });

            // 5. データベース処理が成功した場合のみ画像処理を実行
            // thumbnailDeleteがtrueの場合は画像削除
            // thumbnailDeleteがfalseの場合、
            // 1) 画像ファイルが存在する場合はアップロード
            // 2) 画像ファイルが存在しない場合は現状維持
            $thumbnail_url = $recipe->thumbnail_url; // 既存のURLを保持
            $thumbnail_width = $recipe->thumbnail_width; // 既存のwidthを保持
            $thumbnail_height = $recipe->thumbnail_height; // 既存のheightを保持

            if ($request->has('thumbnailDelete') && $request->boolean('thumbnailDelete')) {
                // 画像削除処理
                $this->imageService->deleteImage($recipe->thumbnail_url);
                $thumbnail_url = null;
                $thumbnail_width = null;
                $thumbnail_height = null;
            } elseif ($request->hasFile('thumbnail')) {
                // 画像アップロード処理
                $imageData = $this->imageService->uploadImage(
                    $request->file('thumbnail'),
                    "$group->id/recipes/thumbnails",
                    $recipe->thumbnail_url
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

            $updatedItem = $group->recipes()->where('id', $id)->with(['categories', 'ingredients'])->first();

            return response()->json($this->formatRecipeResponse($updatedItem), 200);
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
        if ($recipe->thumbnail_url) {
            $this->imageService->deleteImage($recipe->thumbnail_url);
        }

        $recipe->delete();

        return response()->json(['id' => $deletedId], 200);
    }

    /**
     * レシピリクエストのバリデーション
     */
    private function validateRecipeRequest(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'steps' => 'nullable|string',
            'steps.*.id' => 'nullable|string',
            'steps.*.instruction' => 'nullable|string',
            'steps.*.order' => 'nullable|integer',
            'memo' => 'nullable|string',
        ], [
            'name.required' => 'レシピ名を入力してください。',
            'name.string' => 'レシピ名は文字列で入力してください。',
            'name.max' => 'レシピ名は255文字以内で入力してください。',
            'url.string' => 'URLは文字列で入力してください。',
            'url.max' => 'URLは2048文字以内で入力してください。',
            'steps.*.id.string' => '手順IDは文字列で入力してください。',
            'steps.*.instruction.string' => '手順は文字列で入力してください。',
            'steps.*.image.array' => '手順の画像は配列で入力してください。',
            'steps.*.image.url.string' => '手順の画像URLは文字列で入力してください。',
            'steps.*.image.width.integer' => '手順の画像幅は整数で入力してください。',
            'steps.*.image.height.integer' => '手順の画像高さは整数で入力してください。',
            'steps.*.order.integer' => '手順の順番は整数で入力してください。',
            'memo.string' => 'メモは文字列で入力してください。',
        ]);

        // 画像ファイルの検証（アップロードする場合のみ）
        if ($request->hasFile('thumbnail')) {
            $request->validate([
                'thumbnail' => $this->imageService->getValidationRules()
            ]);
        }
    }

    /**
     * カテゴリーの同期処理
     */
    private function syncCategories(Recipe $recipe, $categoryIds, bool $isUpdate = false): void
    {
        if (empty($categoryIds)) {
            return;
        }

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

        if ($isUpdate) {
            $recipe->categories()->sync($existingCategoryIds);
        } else {
            $recipe->categories()->attach($existingCategoryIds);
        }
    }

    /**
     * 食材の同期処理
     */
    private function syncIngredients(Recipe $recipe, $ingredients, bool $isUpdate = false, Group $group): void
    {
        if (empty($ingredients)) {
            return;
        }

        $ingredients = is_string($ingredients)
            ? json_decode($ingredients, true)
            : $ingredients;

        // 連想配列（単一オブジェクト）の場合は配列でラップ
        if (!empty($ingredients) && is_array($ingredients) && !array_key_exists(0, $ingredients)) {
            $ingredients = [$ingredients];
        }

        $ingredientData = collect($ingredients)->map(fn($item) => [
            'id' => $item['id'] ?? null,
            'name' => $item['name'],
        ])->toArray();

        // groupが渡されていない場合はrecipeから取得
        $ids = $this->findOrCreateIds($ingredientData, $group ?? $recipe->group, Ingredient::class);

        // インデックスを保持してマッピング
        $data = [];
        foreach ($ingredients as $idx => $item) {
            if (isset($ids[$idx])) {
                $data[$ids[$idx]] = [
                    'quantity' => $item['quantity'] ?? null,
                    'unit_id' => $item['unitId'],
                    'order' => $item['order'] ?? 0
                ];
            }
        }

        if ($isUpdate) {
            $recipe->ingredients()->sync($data);
        } else {
            $recipe->ingredients()->attach($data);
        }
    }

    /**
     * 手順の同期処理
     */
    private function syncSteps(Recipe $recipe, $steps, bool $isUpdate = false): void
    {
        if (empty($steps)) {
            return;
        }

        $steps = is_string($steps)
            ? json_decode($steps, true)
            : $steps;

        // 連想配列（単一オブジェクト）の場合は配列でラップ
        if (!empty($steps) && is_array($steps) && !array_key_exists(0, $steps)) {
            $steps = [$steps];
        }

        $stepData = collect($steps)->map(fn($item) => [
            'id' => $item['id'] ?? null,
            'instruction' => $item['instruction'],
            'image_url' => $item['image']['url'] ?? null,
            'image_width' => $item['image']['width'] ?? null,
            'image_height' => $item['image']['height'] ?? null,
            'order' => $item['order'] ?? 0,
        ])->toArray();

        if ($isUpdate) {
            $recipe->steps()->sync($stepData);
        } else {
            $recipe->steps()->attach($stepData);
        }
    }

    /**
     * レシピレスポンスのフォーマット
     */
    private function formatRecipeResponse(Recipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'thumbnail' => $recipe->thumbnail_url ? [
                'url' => $recipe->thumbnail_url,
                'width' => $recipe->thumbnail_width,
                'height' => $recipe->thumbnail_height,
            ] : null,
            'url' => $recipe->url,
            'steps' => $recipe->steps->map(fn($item) => [
                'id' => $item->id,
                'instruction' => $item->instruction,
                'image' => $item->image_url ? [
                    'url' => $item->image_url,
                    'width' => $item->image_width,
                    'height' => $item->image_height,
                ] : null,
                'order' => $item->order,
            ]),
            'memo' => $recipe->memo,
            'categories' => $recipe->categories->sortBy('order')->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ]),

            'ingredients' => $recipe->ingredients->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
                'unitId' => $item->pivot->unit_id,
                'order' => $item->pivot->order
            ]),
        ];
    }
}
