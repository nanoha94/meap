<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Group;
use App\Models\Image;
use App\Services\ImageService;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeController extends ApiController
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
        try {
            $user = $request->user();
            $group = $user->group;

            // ページネーションのパラメータを取得（デフォルト値も設定）
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            // TODO: 無限スクロール対応？
            $recipes = $group->recipes()->select('id', 'name', 'url', 'memo')->with(['categories', 'ingredients', 'steps', 'thumbnails'])->get();

            $formattedData = $recipes->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'thumbnail' => $recipe->thumbnails->first() ? [
                        'url' => $recipe->thumbnails->first()->src,
                        'width' => $recipe->thumbnails->first()->width,
                        'height' => $recipe->thumbnails->first()->height,
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
                        'categoryId' => $item->pivot->category_id,
                        'order' => $item->pivot->order
                    ])
                ];
            });
            return $this->indexResponse($formattedData, $formattedData->count(), 'レシピを' . $formattedData->count() . '件取得しました。');
        } catch (Exception $e) {
            Log::error('Recipe index failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, 'レシピの取得に失敗しました。');
        }
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
        try {
            $user = $request->user();
            $group = $user->group;

            // リクエストデータのバリデーション
            $this->validateRecipeRequest($request);

            $recipe = DB::transaction(function () use ($request, $group) {
                // データベース処理を先に実行（画像情報は後で設定）
                $recipe = Recipe::create([
                    'group_id' => $group->id,
                    'name' => $request->name,
                    'url' => $request->url,
                    'memo' => $request->memo,
                ]);

                // カテゴリーを紐づけ
                $this->syncCategories($recipe, $request->categoryIds, false);

                // 食材を紐づけ
                $this->syncIngredients($recipe, $request->ingredients, false, $group);

                // 手順を紐づけ
                // $this->syncSteps($recipe, $request->steps, false, $group);

                return $recipe;
            });

            $thumbnailError = null;

            // 画像ファイルが存在する場合はアップロード（トランザクション外で実行）
            if ($request->hasFile('thumbnail')) {
                try {
                    $image = $this->imageService->uploadAndSaveImage(
                        $request->file('thumbnail'),
                        "$group->id/recipes/thumbnails"
                    );

                    // レシピとサムネイルを紐づけ
                    $recipe->thumbnails()->attach($image->id, [
                        'group_id' => $recipe->group_id,
                        'related_model' => Recipe::class,
                        'image_type' => 'thumbnail',
                        'order' => 0
                    ]);
                } catch (Exception $e) {
                    Log::error('Recipe thumbnail upload failed:', [
                        'recipe_id' => $recipe->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $thumbnailError = '画像のアップロードに失敗しました。';
                }
            }

            $response = $this->formatRecipeResponse($recipe);

            // 画像アップロードに失敗した場合は警告メッセージを含める
            if ($thumbnailError) {
                return $this->successResponseWithWarning($response, 'レシピを作成しました。', $thumbnailError);
            }

            return $this->successResponse($response, 'レシピを作成しました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            Log::error('Recipe store failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, 'レシピの作成に失敗しました。');
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
        try {
            $user = $request->user();
            $group = $user->group;

            $recipe = $group->recipes()->where('id', $id)->with(['categories', 'ingredients', 'steps'])->first();
            if (!$recipe) {
                return $this->notFoundResponse('指定されたレコードが見つかりません。');
            }

            return $this->successResponse($this->formatRecipeResponse($recipe));
        } catch (Exception $e) {
            Log::error('Recipe show failed:', [
                'recipe_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, 'レシピの取得に失敗しました。');
        }
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
        try {
            $user = $request->user();
            $group = $user->group;

            $recipe = $group->recipes()->where('id', $id)->first();
            if (!$recipe) {
                return $this->notFoundResponse('指定されたレコードが見つかりません。');
            }

            // リクエストデータのバリデーション
            $this->validateRecipeRequest($request);

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

            // 画像処理（トランザクション外で実行）
            $thumbnailError = $this->handleThumbnailUpdate($request, $recipe, $group);

            $updatedItem = $group->recipes()->where('id', $id)->with(['categories', 'ingredients'])->first();
            $response = $this->formatRecipeResponse($updatedItem);

            // 画像処理に失敗した場合は警告メッセージを含める
            if ($thumbnailError) {
                return $this->successResponseWithWarning($response, 'レシピ(' . $request->name . ')を更新しました。', $thumbnailError);
            }

            return $this->updatedResponse($response, 'レシピ(' . $request->name . ')を更新しました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            Log::error('Recipe update failed:', [
                'recipe_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, 'レシピの更新に失敗しました。');
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
        try {
            $user = $request->user();
            $group = $user->group;

            $recipe = $group->recipes()->where('id', $id)->first();

            if (!$recipe) {
                return $this->notFoundResponse('指定されたレコードが見つかりません。');
            }

            $recipeName = $recipe->name;

            DB::transaction(function () use ($recipe) {
                // 画像ファイルを削除
                $existingThumbnail = $recipe->thumbnails()->first();
                if ($existingThumbnail) {
                    // 画像レコードを削除
                    $this->imageService->deleteImageRecord($existingThumbnail);
                }

                // レシピを削除
                $recipe->delete();
            });

            return $this->deletedResponse('レシピ(' . $recipeName . ')を削除しました。');
        } catch (Exception $e) {
            Log::error('Recipe destroy failed:', [
                'recipe_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, 'レシピの削除に失敗しました。');
        }
    }

    /**
     * サムネイル更新処理
     * @return string|null エラーメッセージ（成功時はnull）
     */
    private function handleThumbnailUpdate(Request $request, Recipe $recipe, Group $group): ?string
    {
        try {
            // thumbnailDeleteがtrueの場合は画像削除
            // 更新のときにフロントから画像を送信したくないので、thumbnailDeleteで更新か削除かを判断する
            if ($request->has('thumbnailDelete') && $request->boolean('thumbnailDelete')) {
                $existingThumbnail = $recipe->thumbnails()->first();
                if ($existingThumbnail) {
                    $this->imageService->deleteImageRecord($existingThumbnail);
                }
            }
            // 画像アップロード
            elseif ($request->hasFile('thumbnail')) {
                $existingThumbnail = $recipe->thumbnails()->first();
                // 既存のサムネイルがある場合は更新
                if ($existingThumbnail) {
                    $this->imageService->updateImage(
                        $request->file('thumbnail'),
                        "$group->id/recipes/thumbnails",
                        $existingThumbnail
                    );
                }
                // 既存のサムネイルがない場合は新規作成
                else {
                    $image = $this->imageService->uploadAndSaveImage(
                        $request->file('thumbnail'),
                        "$group->id/recipes/thumbnails"
                    );

                    // レシピとサムネイルの紐づけ
                    $recipe->thumbnails()->attach($image->id, [
                        'group_id' => $recipe->group_id,
                        'related_model' => Recipe::class,
                        'image_type' => 'thumbnail',
                        'order' => 0
                    ]);
                }
            }
            // 成功した場合はnullを返す
            return null;
        } catch (Exception $e) {
            Log::error('Recipe thumbnail update failed:', [
                'recipe_id' => $recipe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // 失敗した場合はエラーメッセージを返す
            return '画像のアップロードに失敗しました。';
        }
    }

    /**
     * レシピリクエストのバリデーション
     */
    private function validateRecipeRequest(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'ingredients' => 'nullable|string',
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

        // ingredientsのバリデーション
        if ($request->has('ingredients') && !empty($request->ingredients)) {
            $this->validateIngredients($request->ingredients);
        }
    }

    /**
     * ingredientsのバリデーション
     */
    private function validateIngredients($ingredients): void
    {
        // JSON文字列の場合はデコード
        if (is_string($ingredients)) {
            $ingredients = json_decode($ingredients, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => 'ingredientsのJSON形式が正しくありません。'], 422)
                );
            }
        }

        // 配列でない場合はエラー
        if (!is_array($ingredients)) {
            throw new ValidationException(
                validator([], []),
                response()->json(['message' => 'ingredientsは配列形式で指定してください。'], 422)
            );
        }

        // 連想配列（単一オブジェクト）の場合は配列でラップ
        if (!empty($ingredients) && !array_key_exists(0, $ingredients)) {
            $ingredients = [$ingredients];
        }

        // 各食材のバリデーション
        foreach ($ingredients as $index => $ingredient) {
            if (!is_array($ingredient)) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} はオブジェクト形式で指定してください。"], 422)
                );
            }

            // 必須フィールドのチェック
            if (!isset($ingredient['name']) || empty($ingredient['name'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の名前が指定されていません。"], 422)
                );
            }

            if (!isset($ingredient['unitId']) || empty($ingredient['unitId'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の単位IDが指定されていません。"], 422)
                );
            }

            if (!isset($ingredient['categoryId']) || empty($ingredient['categoryId'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} のカテゴリIDが指定されていません。"], 422)
                );
            }

            // データ型のチェック
            if (!is_string($ingredient['name'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の名前は文字列で指定してください。"], 422)
                );
            }

            if (!is_string($ingredient['unitId'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の単位IDは文字列で指定してください。"], 422)
                );
            }

            if (!is_string($ingredient['categoryId'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} のカテゴリIDは文字列で指定してください。"], 422)
                );
            }

            // オプションフィールドのチェック
            if (isset($ingredient['quantity']) && !is_numeric($ingredient['quantity'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の数量は数値で指定してください。"], 422)
                );
            }

            if (isset($ingredient['order']) && !is_numeric($ingredient['order'])) {
                throw new ValidationException(
                    validator([], []),
                    response()->json(['message' => "食材 {$index} の順番は数値で指定してください。"], 422)
                );
            }
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

        $existingCategoryIds = $recipe->group->recipeCategories()
            ->whereIn('id', $categoryIds)
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

        // 食材IDを取得
        $ids = $this->findOrCreateIds($ingredientData, $group, Ingredient::class);

        // インデックスを保持してマッピング
        $data = [];
        foreach ($ingredients as $idx => $item) {
            if (isset($ids[$idx])) {
                $data[$ids[$idx]] = [
                    'quantity' => $item['quantity'] ?? null,
                    'unit_id' => $item['unitId'],
                    'category_id' => $item['categoryId'],
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
            'thumbnail' => $recipe->thumbnails->first() ? [
                'url' => $recipe->thumbnails->first()->src,
                'width' => $recipe->thumbnails->first()->width,
                'height' => $recipe->thumbnails->first()->height,
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
                'categoryId' => $item->pivot->category_id,
                'order' => $item->pivot->order
            ]),
        ];
    }
}
