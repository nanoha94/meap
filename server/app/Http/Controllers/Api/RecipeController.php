<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Image;
use App\Models\RecipeStep;
use App\Services\ImageService;
use App\Services\RecipeService;
use App\Traits\AutoComplement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends ApiController
{
    use AutoComplement;

    protected ImageService $imageService;
    protected RecipeService $recipeService;

    public function __construct(ImageService $imageService, RecipeService $recipeService)
    {
        $this->imageService = $imageService;
        $this->recipeService = $recipeService;
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

            // TODO: 将来的に無限スクロール対応を検討（現在は全件取得）
            $recipes = $group->recipes()->select('id', 'name', 'url', 'memo')->with(['categories', 'ingredients', 'steps', 'thumbnails'])->get();

            $formattedData = $recipes->map(function ($recipe) {
                return $this->recipeService->formatCompleteRecipeResponse($recipe);
            });
            return $this->indexResponse($formattedData, $formattedData->count(), __('api.recipe.list_retrieved', ['count' => $formattedData->count()]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.recipe.get_failed'),
                'recipe.index',
            );
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
                $this->syncThumbnail($recipe, $request->thumbnailId);

                // カテゴリーを紐づけ
                $this->syncCategories($recipe, $request->categoryIds);

                // 食材を紐づけ
                $this->syncIngredients($recipe, $request->ingredients);

                // 手順を紐づけ
                $this->syncSteps($recipe, $request->steps);

                return $recipe;
            });

            $response = $this->recipeService->formatCompleteRecipeResponse($recipe);

            return $this->successResponse($response, __('api.recipe.created', ['name' => $request->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.recipe.creation_failed'),
                'recipe.store'
            );
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
                return $this->notFoundResponse(__('api.general.not_found'));
            }

            return $this->successResponse($this->recipeService->formatCompleteRecipeResponse($recipe), __('api.recipe.details_retrieved', ['name' => $recipe->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.recipe.get_failed'),
                'recipe.show'
            );
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

            $recipe = $group->recipes()->where('id', $id)->with(['categories', 'ingredients', 'steps'])->first();
            if (!$recipe) {
                return $this->notFoundResponse(__('api.general.not_found'));
            }

            // リクエストデータのバリデーション
            $this->validateRecipeRequest($request);

            DB::transaction(function () use ($request, $recipe) {
                $recipe->update([
                    'name' => $request->name,
                    'url' => $request->url,
                    'memo' => $request->memo,
                ]);

                $this->syncThumbnail($recipe, $request->thumbnailId);
                $this->syncCategories($recipe, $request->categoryIds);
                $this->syncIngredients($recipe, $request->ingredients);
                $this->syncSteps($recipe, $request->steps);
            });

            // 既存の$recipeを使用し、必要なリレーションをロード
            $recipe->load(['categories', 'ingredients', 'thumbnails', 'steps']);
            $response = $this->recipeService->formatCompleteRecipeResponse($recipe);

            return $this->updatedResponse($response, __('api.recipe.updated', ['name' => $request->name]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.recipe.update_failed'),
                'recipe.update'
            );
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
                return $this->notFoundResponse(__('api.general.not_found'));
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

            return $this->deletedResponse(__('api.recipe.deleted', ['name' => $recipeName]));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.recipe.deletion_failed'),
                'recipe.destroy'
            );
        }
    }

    /**
     * レシピリクエストのバリデーション
     */
    private function validateRecipeRequest(Request $request): void
    {
        // TODO: バリデーションチェックはフォームリクエストに移行する
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
            'name.required' => __('validation_custom.recipe.name.required'),
            'name.string' => __('validation_custom.recipe.name.string'),
            'name.max' => __('validation_custom.recipe.name.max'),
            'url.string' => __('validation_custom.recipe.url.string'),
            'url.max' => __('validation_custom.recipe.url.max'),
            'steps.*.id.string' => __('validation_custom.recipe.steps.id.string'),
            'steps.*.instruction.string' => __('validation_custom.recipe.steps.instruction.string'),
            'steps.*.image.array' => __('validation_custom.recipe.steps.image.array'),
            'steps.*.image.url.string' => __('validation_custom.recipe.steps.image.url.string'),
            'steps.*.image.width.integer' => __('validation_custom.recipe.steps.image.width.integer'),
            'steps.*.image.height.integer' => __('validation_custom.recipe.steps.image.height.integer'),
            'steps.*.order.integer' => __('validation_custom.recipe.steps.order.integer'),
            'memo.string' => __('validation_custom.recipe.memo.string'),
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

    private function syncThumbnail(Recipe $recipe, $thumbnailId): void
    {
        if (!$thumbnailId) {
            return;
        }

        // サムネイル画像を紐づけ
        $image = Image::find($thumbnailId);
        if (!$image) {
            // 例外をスローしてトランザクションをロールバック
            throw new Exception(__('api.recipe.thumbnail_not_found', [
                'thumbnail_id' => $thumbnailId
            ]));
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
    private function syncCategories(Recipe $recipe, $categoryIds): void
    {
        if (empty($categoryIds) || !is_array($categoryIds)) {
            return;
        }

        $existingCategoryIds = $recipe->group->recipeCategories()
            ->whereIn('id', $categoryIds)
            ->pluck('id')
            ->toArray();


        $recipe->categories()->sync($existingCategoryIds);
    }

    /**
     * 食材の同期処理
     */
    private function syncIngredients(Recipe $recipe, $ingredients): void
    {
        if (empty($ingredients) || !is_array($ingredients)) {
            return;
        }

        $ingredientData = collect($ingredients)->map(fn($item) => [
            'id' => $item['id'] ?? null,
            'name' => $item['name'],
        ])->toArray();

        // 食材IDを取得
        $ids = $this->findOrCreateIds($ingredientData, $recipe->group, Ingredient::class);

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

        // 新規作成時も更新時もsyncを使用
        $recipe->ingredients()->sync($data);
    }

    /**
     * 手順の同期処理
     */
    private function syncSteps(Recipe $recipe, $steps): void
    {
        // 手順と画像を事前にロード
        $recipe->load(['steps.images']);

        // 画像IDをまとめて取得（$stepsがnullの場合は空配列）
        $imageIds = $steps ? collect($steps)->pluck('imageId')->filter()->unique()->values()->toArray() : [];
        $images = !empty($imageIds) ? Image::whereIn('id', $imageIds)->get()->keyBy('id') : collect();

        $syncData = [];
        $stepsToDelete = [];
        $imagesToDelete = [];

        // 既存の手順を処理
        foreach ($recipe->steps as $step) {
            $stepId = $step->id;

            // $stepsから該当する手順を検索
            $stepData = null;
            if ($steps && is_array($steps)) {
                foreach ($steps as $stepItem) {
                    if (isset($stepItem['id']) && $stepItem['id'] === $stepId) {
                        $stepData = $stepItem;
                        break;
                    }
                }
            }

            if ($stepData) {
                // 手順の内容が変更されているかチェック
                if ($step->instruction !== $stepData['instruction']) {
                    $step->update(['instruction' => $stepData['instruction']]);
                }

                // 画像の処理
                $imageId = $stepData['imageId'] ?? null;
                $currentImageId = $step->images->first()?->id;

                if ($imageId !== $currentImageId) {
                    // 既存画像を削除対象に追加
                    if ($step->images->isNotEmpty()) {
                        $imagesToDelete = array_merge($imagesToDelete, $step->images->pluck('id')->toArray());
                    }

                    // 新しい画像を関連付け
                    if (!empty($imageId) && isset($images[$imageId]) && $images[$imageId]->group_id === $recipe->group_id) {
                        $step->images()->attach($imageId, [
                            'group_id' => $recipe->group_id,
                            'related_model' => RecipeStep::class,
                            'image_type' => 'image',
                            'order' => 0
                        ]);
                    }
                }

                $syncData[$step->id] = ['order' => $stepData['order'] ?? 0];
            } else {
                // 削除対象に追加
                $stepsToDelete[] = $step->id;
                if ($step->images->isNotEmpty()) {
                    $imagesToDelete = array_merge($imagesToDelete, $step->images->pluck('id')->toArray());
                }
            }
        }

        // 新規作成
        if ($steps && is_array($steps)) {
            foreach ($steps as $index => $stepData) {
                // IDが存在しない場合は新規作成
                if (!isset($stepData['id']) || empty($stepData['id'])) {
                    $currentStep = RecipeStep::create([
                        'recipe_id' => $recipe->id,
                        'instruction' => $stepData['instruction'],
                    ]);

                    $imageId = $stepData['imageId'] ?? null;
                    if (!empty($imageId) && isset($images[$imageId]) && $images[$imageId]->group_id === $recipe->group_id) {
                        $currentStep->images()->attach($imageId, [
                            'group_id' => $recipe->group_id,
                            'related_model' => RecipeStep::class,
                            'image_type' => 'image',
                            'order' => 0
                        ]);
                    }

                    $syncData[$currentStep->id] = [
                        'order' => $stepData['order'] ?? ($index + 1)
                    ];
                }
            }
        }

        // 一括削除
        if (!empty($stepsToDelete)) {
            RecipeStep::whereIn('id', $stepsToDelete)->delete();
        }

        // 一括画像削除
        if (!empty($imagesToDelete)) {
            $this->imageService->deleteImages(array_unique($imagesToDelete));
        }

        // 一括更新
        if (!empty($syncData)) {
            $recipe->steps()->sync($syncData);
        }
    }
}
