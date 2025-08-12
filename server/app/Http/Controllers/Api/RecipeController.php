<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Group;
use App\Models\Image;
use App\Models\RecipeStep;
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
                $recipe = Recipe::create([
                    'group_id' => $group->id,
                    'name' => $request->name,
                    'url' => $request->url,
                    'memo' => $request->memo,
                ]);

                // サムネイルを紐づけ
                $this->syncThumbnail($recipe, $request->thumbnailId, false);

                // カテゴリーを紐づけ
                $this->syncCategories($recipe, $request->categoryIds, false);

                // 食材を紐づけ
                $this->syncIngredients($recipe, $request->ingredients, false, $group);

                // 手順を紐づけ
                // $this->syncSteps($recipe, $request->steps, false, $group);


                return $recipe;
            });

            $response = $this->formatRecipeResponse($recipe);

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
                    'memo' => $request->memo,
                ]);

                // サムネイルを紐づけ
                $this->syncThumbnail($recipe, $request->thumbnailId, true);

                // 2. カテゴリー更新
                $this->syncCategories($recipe, $request->categoryIds, true);

                // 3. 食材更新
                $this->syncIngredients($recipe, $request->ingredients, true, $group);

                // 4. 手順更新（必要に応じて実装）
                // $this->syncSteps($recipe, $request->steps, true, $group);

                return true;
            });

            // 既存の$recipeを使用し、必要なリレーションをロード
            $recipe->load(['categories', 'ingredients', 'thumbnails', 'steps']);
            $response = $this->formatRecipeResponse($recipe);

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
                    $this->imageService->deleteImages([$existingThumbnail->id]);
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
     * レシピリクエストのバリデーション
     */
    private function validateRecipeRequest(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'required|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.name' => 'required|string',
            'ingredients.*.unitId' => 'required|string',
            'ingredients.*.categoryId' => 'required|string',
            'ingredients.*.quantity' => 'nullable|numeric',
            'ingredients.*.order' => 'nullable|integer',
            'steps' => 'nullable|array',
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

        // step_imagesのバリデーション
        if ($request->hasFile('step_images')) {
            $request->validate([
                'step_images.*' => $this->imageService->getValidationRules()
            ]);
        }
    }

    private function syncThumbnail(Recipe $recipe, $thumbnailId, bool $isUpdate = false): void
    {
        if (!$thumbnailId) {
            return;
        }

        // サムネイル画像を紐づけ
        $image = Image::find($thumbnailId);
        if (!$image) {
            throw new Exception('サムネイル画像が見つかりません。');
        }
        // レシピとサムネイルを紐づけ
        $recipe->thumbnails()->attach($image->id, [
            'group_id' => $recipe->group_id,
            'related_model' => Recipe::class,
            'image_type' => 'thumbnail',
            'order' => 0
        ]);
    }

    /**
     * カテゴリーの同期処理
     */
    private function syncCategories(Recipe $recipe, $categoryIds, bool $isUpdate = false): void
    {
        if (empty($categoryIds) || !is_array($categoryIds)) {
            return;
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
        if (empty($ingredients) || !is_array($ingredients)) {
            return;
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
    // private function syncSteps(Recipe $recipe, $steps, bool $isUpdate = false, Group $group): void
    // {
    //     if (empty($steps) || !is_array($steps)) {
    //         return;
    //     }

    //     // JSONリクエストでは既に配列として渡されるため、文字列処理は不要

    //     foreach ($steps as $stepData) {
    //         $createdStep = RecipeStep::create([
    //             'recipe_id' => $recipe->id,
    //             'instruction' => $stepData['instruction'],
    //             'order' => $stepData['order'] ?? 0
    //         ]);

    //         // 画像がある場合の処理
    //         if (isset($stepData['image']) && !empty($stepData['image'])) {
    //             $imageData = $stepData['image'];

    //             // URLが指定されている場合（既存の画像またはアップロード済み画像）
    //             if (isset($imageData['url']) && !empty($imageData['url'])) {
    //                 try {
    //                     // 画像URLから画像情報を取得または作成
    //                     $image = $this->processStepImageUrl($imageData['url'], $group);

    //                     if ($image) {
    //                         // レシピステップと画像を紐づけ
    //                         $createdStep->images()->attach($image->id, [
    //                             'group_id' => $recipe->group_id,
    //                             'related_model' => RecipeStep::class,
    //                             'image_type' => 'image',
    //                             'order' => 0
    //                         ]);
    //                     }
    //                 } catch (Exception $e) {
    //                     Log::error('Recipe step image processing failed:', [
    //                         'step_id' => $createdStep->id,
    //                         'url' => $imageData['url'],
    //                         'error' => $e->getMessage(),
    //                         'trace' => $e->getTraceAsString()
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
    // }

    /**
     * ステップ画像URLを処理
     */
    // private function processStepImageUrl(string $url, Group $group): ?Image
    // {
    //     // 既存の画像を検索
    //     $existingImage = Image::where('src', $url)->first();

    //     if ($existingImage) {
    //         return $existingImage;
    //     }

    //     // 新しい画像として作成（URLが外部URLの場合）
    //     if (filter_var($url, FILTER_VALIDATE_URL)) {
    //         return Image::create([
    //             'src' => $url,
    //             'width' => 0, // 後で取得可能
    //             'height' => 0, // 後で取得可能
    //         ]);
    //     }

    //     return null;
    // }

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
