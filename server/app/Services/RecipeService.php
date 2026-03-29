<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeStep;
use App\Traits\AutoComplement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class RecipeService extends AbstractDomainService
{
    use AutoComplement;
    protected ImageService $imageService;
    protected RecipeCategoryService $recipeCategoryService;
    protected IngredientCategoryService $ingredientCategoryService;
    protected IngredientUnitService $ingredientUnitService;

    public function __construct(
        ImageService $imageService,
        RecipeCategoryService $recipeCategoryService,
        IngredientCategoryService $ingredientCategoryService,
        IngredientUnitService $ingredientUnitService
    ) {
        $this->imageService = $imageService;
        $this->recipeCategoryService = $recipeCategoryService;
        $this->ingredientCategoryService = $ingredientCategoryService;
        $this->ingredientUnitService = $ingredientUnitService;
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.recipe');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->recipes();
    }

    /**
     * 前回献立日を算出するサブクエリ（SELECT 用は " as last_planned_date" を付与して使用）
     */
    protected function getLastPlannedDateSubquery(): string
    {
        return '(SELECT MAX(mp.date) FROM meal_recipe_mappings mrm ' .
            'JOIN meals m ON mrm.meal_id = m.id JOIN meal_plans mp ON m.meal_plan_id = mp.id ' .
            'WHERE mrm.recipe_id = recipes.id)';
    }

    protected function getSelectColumns(): array
    {
        return [
            'id',
            'group_id',
            'owner_user_id',
            'name',
            'url',
            'memo',
            'serving_count',
            'cooking_time',
            DB::raw($this->getLastPlannedDateSubquery() . ' as last_planned_date'),
            'status',
        ];
    }

    protected function getWithColumns(): array
    {
        return ['categories', 'ingredients', 'ingredientCategories', 'ingredientUnits', 'steps.images', 'thumbnails', 'group'];
    }

    /**
     * レシピ一覧取得（並び替え対応）
     *
     * @param Group $group
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $sort
     * @param string|null $order
     * @param array $filters
     * @return array
     */
    public function index(Group $group, ?int $limit = 15, ?int $offset = 0, ?string $sort = 'created_at', ?string $order = 'desc', array $filters = []): array
    {
        $query = $this->getGroupRelation($group)
            ->select($this->getSelectColumns())
            ->with($this->getWithColumns());

        // フィルタ条件
        if (!empty($filters['recipe_name'])) {
            $keywords = preg_split('/[\s　]+/u', trim($filters['recipe_name']), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $query->where('recipes.name', 'ilike', '%' . $keyword . '%');
            }
        }

        if (!empty($filters['ingredient_name'])) {
            $keywords = preg_split('/[\s　]+/u', trim($filters['ingredient_name']), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $query->whereHas('ingredients', function ($q) use ($keyword) {
                    $q->where('ingredients.name', 'ilike', '%' . $keyword . '%');
                });
            }
        }

        if (!empty($filters['category_ids'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('recipe_categories.id', $filters['category_ids']);
            });
        }

        $lastPlannedDateSubquery = $this->getLastPlannedDateSubquery();
        if (!empty($filters['last_planned_date_from'])) {
            $query->whereRaw("{$lastPlannedDateSubquery} >= ?", [$filters['last_planned_date_from']]);
        }
        if (!empty($filters['last_planned_date_to'])) {
            $query->whereRaw("{$lastPlannedDateSubquery} <= ?", [$filters['last_planned_date_to']]);
        }

        // 並び替えロジック
        switch ($sort) {
            case 'name':
                $query->orderBy('name', $order);
                break;
            case 'last_planned_date':
                if (strtolower($order) === 'desc') {
                    $query->orderByRaw('last_planned_date DESC NULLS LAST');
                } else {
                    $query->orderByRaw('last_planned_date ASC NULLS LAST');
                }
                break;
            default:
                // created_at などそのまま指定されるカラムは orderBy で対応
                $query->orderBy($sort ?? 'created_at', $order ?? 'desc');
                break;
        }

        $total = $query->count();
        $items = $query->skip($offset)->take($limit)->get();
        $data = $items->map(fn($item) => $this->formatIndexResponse($item))->toArray();

        return ['data' => $data, 'total' => $total];
    }

    protected function getCreateFields(): array
    {
        return [
            'name' => 'name',
            'url' => 'url',
            'memo' => 'memo',
            'serving_count' => 'servingCount',
            'cooking_time' => 'cookingTime',
            'owner_user_id' => 'ownerUserId',
        ];
    }

    protected function getUpdateFields(): array
    {
        return [
            'name' => 'name',
            'url' => 'url',
            'memo' => 'memo',
            'serving_count' => 'servingCount',
            'cooking_time' => 'cookingTime',
            'owner_user_id' => 'ownerUserId',
        ];
    }

    /**
     * レシピ一覧用フォーマット
     * @param Recipe $recipe
     * @return array
     */
    public function formatRecipeListItem(Recipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'categories' => $this->formatRecipeCategories($recipe->categories),
            'thumbnail' => $this->imageService->formatImage($recipe->thumbnails->first()),
            'lastPlannedDate' => $recipe->last_planned_date ?? null,
            'cookingTime' => $recipe->cooking_time,
        ];
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, Recipe::class);

        // groupが読み込まれていない場合は読み込む
        if (!$item->relationLoaded('group')) {
            $item->load('group');
        }

        return $this->formatRecipeListItem($item);
    }

    protected function formatShowResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, Recipe::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'thumbnail' => $this->imageService->formatImage($item->thumbnails->first()),
            'url' => $item->url,
            'steps' => $this->formatRecipeSteps($item->steps->sortBy('order')),
            'memo' => $item->memo,
            'servingCount' => $item->serving_count,
            'categories' => $this->formatRecipeCategories($item->categories),
            'ingredients' => $this->formatRecipeIngredients($item, $item->group),
            'ownerUserId' => $item->owner_user_id,
            'status' => $item->status,
            'publishedRecipeId' => $item->published_recipe_id,
        ];
    }

    /**
     * レシピを作成（サムネイル、カテゴリ、食材、手順を含む）
     */
    public function create(array $data, Group $group): void
    {
        DB::transaction(function () use ($data, $group) {
            $createData = [];
            foreach ($this->getCreateFields() as $field => $dataKey) {
                $createData[$field] = $data[$dataKey] ?? null;
            }

            // TODO: published_recipe_idはセカンドリリースで設定するため、ファーストリリースではnullを設定
            $createData['published_recipe_id'] = null;

            $item = $this->getGroupRelation($group)->create($createData);

            // サムネイルを紐づけ
            if (!empty($data['thumbnailId'])) {
                $this->attachThumbnail($item, $data['thumbnailId'], $group);
            }

            // カテゴリーを紐づけ
            if (!empty($data['categoryIds'])) {
                $this->syncCategories($item, $data['categoryIds'], $group);
            }

            // 食材を紐づけ
            if (!empty($data['ingredients'])) {
                $this->syncIngredients($item, $data['ingredients'], $group);
            }

            // 手順を紐づけ
            if (!empty($data['steps'])) {
                $this->updateSteps($item, $data['steps'], $group);
            }
        });
    }

    public function update(string $id, array $data, Group $group): void
    {
        DB::transaction(function () use ($id, $data, $group) {
            // 更新対象を取得
            $currentItem = $this->findItemsByIds([$id], $group)->first();
            $updateData = [];
            foreach ($this->getUpdateFields() as $field => $dataKey) {
                $updateData[$field] = $data[$dataKey] ?? null;
            }
            $currentItem->update($updateData);

            // サムネイルの更新処理
            // 既存のサムネイルを取得
            $prevThumbnail = $currentItem->thumbnails->first();
            $newThumbnailId = $data['thumbnailId'] ?? null;

            // 新しいサムネイルが指定されている場合、先に存在確認を行う
            // これにより、存在しないサムネイルの場合は古いサムネイルを削除する前にエラーになる
            if (!empty($newThumbnailId)) {
                $this->imageService->findImagesByIds([$newThumbnailId], $group);
            }

            // 既存のサムネイルがある場合
            if ($prevThumbnail) {
                // 新しいサムネイルが空（nullまたは空文字列）の場合、または変更された場合
                if (empty($newThumbnailId) || $prevThumbnail->id !== $newThumbnailId) {
                    // 古いサムネイルの紐づけを解除
                    $this->imageService->deleteImages([$prevThumbnail->id], $currentItem->id, $group);
                }
            }

            // 新しいサムネイルを紐づけ（空でない場合のみ）
            if (!empty($newThumbnailId) && (!$prevThumbnail || $prevThumbnail->id !== $newThumbnailId)) {
                $this->attachThumbnail($currentItem, $newThumbnailId, $group);
            }

            // カテゴリーを紐づけ
            if (!empty($data['categoryIds'])) {
                $this->syncCategories($currentItem, $data['categoryIds'], $group);
            }

            // 食材を紐づけ
            if (!empty($data['ingredients'])) {
                $this->syncIngredients($currentItem, $data['ingredients'], $group);
            }

            // 手順を紐づけ
            if (!empty($data['steps'])) {
                $this->updateSteps($currentItem, $data['steps'], $group);
            }
        });
    }

    public function delete(string $id, Group $group): Model
    {
        return  DB::transaction(function () use ($id, $group) {
            $item = $this->findItemsByIds([$id], $group)->first();
            $itemName = $item->name;

            // サムネイル画像の紐づけを解除
            $thumbnailIds = $item->thumbnails->pluck('id')->toArray();
            if (!empty($thumbnailIds)) {
                foreach ($thumbnailIds as $thumbnailId) {
                    $this->imageService->deleteImages([$thumbnailId], $item->id, $group);
                }
            }

            // ステップ画像の紐づけを解除（このレシピのステップとの紐づけのみ）
            foreach ($item->steps as $step) {
                $stepImageIds = $step->images->pluck('id')->toArray();
                if (!empty($stepImageIds)) {
                    foreach ($stepImageIds as $stepImageId) {
                        $this->imageService->deleteImages([$stepImageId], $step->id, $group);
                    }
                }
            }

            // レシピを削除（関連データもカスケード削除）
            $item->delete();

            return $item;
        });
    }

    /**
     * レシピの手順情報をフォーマット
     */
    private function formatRecipeSteps(Collection $steps): array
    {
        // 型チェック
        $this->typeCheck($steps, Collection::class);
        $this->typeCheckCollection($steps, RecipeStep::class);

        return $steps->map(fn($item) => [
            'id' => $item->id,
            'instruction' => $item->instruction,
            'image' => $this->imageService->formatImage($item->images->first()),
            'order' => $item->order,
        ])->toArray();
    }

    /**
     * レシピのカテゴリー情報をフォーマット
     */
    public function formatRecipeCategories(Collection $categories): array
    {
        // 型チェック
        $this->typeCheck($categories, Collection::class);
        $this->typeCheckCollection($categories, RecipeCategory::class);

        return $categories->sortBy('order')->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'order' => $item->order,
        ])->toArray();
    }

    /**
     * レシピの食材情報をフォーマット
     */
    public function formatRecipeIngredients(Recipe $recipe, Group $group): array
    {
        // グループの全カテゴリを取得（order順でソート）
        $categories = $group->ingredientCategories()
            ->select('id', 'name', 'order')
            ->orderBy('order')
            ->get()
            ->keyBy('id');

        // 必要なunit_idを収集
        $unitIds = $recipe->ingredientUnits->pluck('id')->toArray();
        $units = collect();
        if (!empty($unitIds)) {
            // findItemsByIdsを使って存在チェック＆グループスコープ検証
            $units = $this->ingredientUnitService->findItemsByIds($unitIds, $group)->keyBy('id');
        }

        // すべてのingredientsを取得し、カテゴリーのorderとingredientのorderでソート
        $result = $recipe->ingredients
            ->map(function ($item) use ($units, $categories) {
                $unit = $units->get($item->pivot->unit_id);
                $category = $categories->get($item->pivot->category_id);
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity !== null ? (float) $item->pivot->quantity : null,
                    'unit' => [
                        'id' => $item->pivot->unit_id,
                        'name' => $unit->name,
                        'position' => $unit->position,
                        'requiresQuantity' => $unit->requires_quantity,
                        'order' => $unit->order
                    ],
                    'categoryId' => $item->pivot->category_id,
                    'order' => $item->pivot->order,
                    // カテゴリーのorderを一時的に追加
                    '_categoryOrder' => $category ? $category->order : PHP_INT_MAX,
                ];
            })
            ->sortBy([
                ['_categoryOrder', 'asc'],
                ['order', 'asc'],
            ])
            ->map(function ($item) {
                // ソート用の一時キーを削除
                unset($item['_categoryOrder']);
                return $item;
            })
            ->values()
            ->toArray();

        return $result;
    }

    /**
     * サムネイルの同期処理
     */
    private function attachThumbnail(Recipe $recipe, string $thumbnailId, Group $group): void
    {
        // 画像の存在とグループスコープを検証
        $this->imageService->findImagesByIds([$thumbnailId], $group);

        // サムネイルを追加
        $recipe->thumbnails()->attach($thumbnailId, [
            'group_id' => $group->id,
            'related_model' => Recipe::class,
            'image_type' => 'thumbnail',
            'order' => 0
        ]);
    }

    /**
     * カテゴリーの同期処理
     */
    private function syncCategories(Recipe $recipe, array $categoryIds, Group $group): void
    {
        if (empty($categoryIds)) {
            $recipe->categories()->sync([]);
            return;
        }

        $existingCategoryIds = $this->recipeCategoryService
            ->findItemsByIds($categoryIds, $group)
            ->pluck('id')
            ->toArray();

        $recipe->categories()->sync($existingCategoryIds);
    }

    /**
     * 食材の同期処理
     */
    private function syncIngredients(Recipe $recipe, array $ingredients, Group $group): void
    {
        // 単位ID存在チェック
        $unitIds = collect($ingredients)->pluck('unitId')->filter()->unique()->toArray();
        if (!empty($unitIds)) {
            $this->ingredientUnitService->findItemsByIds($unitIds, $group);
        }

        // カテゴリID存在チェック
        $categoryIds = collect($ingredients)->pluck('categoryId')->filter()->unique()->toArray();
        if (!empty($categoryIds)) {
            $this->ingredientCategoryService->findItemsByIds($categoryIds, $group);
        }

        $ingredientData = collect($ingredients)->map(fn($item) => [
            'id' => $item['id'] ?? null,
            'name' => $item['name'],
        ])->toArray();

        $ids = $this->findOrCreateIds($ingredientData, $group, Ingredient::class);

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

        $recipe->ingredients()->sync($data);
    }

    /**
     * 手順の更新処理
     * 
     * 既存の手順の更新・削除と新規手順の追加を処理します。
     * リクエストで渡された手順のIDの有無で処理を分岐：
     * - IDあり：既存手順として更新（instruction、order、画像の変更を検知）
     * - IDなし：新規手順として作成
     * - リクエストに含まれない既存手順：削除対象
     * 
     * @param Recipe $recipe レシピモデル
     * @param array $steps 手順データの配列（各要素: id?, instruction, imageId?, order?）
     * @param Group $group グループモデル
     */
    private function updateSteps(Recipe $recipe, array $requestSteps, Group $group): void
    {
        // 画像を事前にロード
        $recipe->load(['steps.images']);

        // リクエストで指定された画像IDを一括取得
        $imageIds = collect($requestSteps)->pluck('imageId')->filter()->unique()->values()->toArray();

        // 画像の存在を事前に検証（破壊的な操作の前に行う）
        $images = !empty($imageIds)
            ? $this->imageService->findImagesByIds($imageIds, $group)
            : collect();

        // リクエストで指定された手順をキー化
        $requestStepsById = collect($requestSteps)->keyBy('id');

        // 削除対象の画像ID
        $imagesToDelete = [];

        // 既存の手順を処理
        foreach ($recipe->steps as $step) {
            $requestStep = $requestStepsById->get($step->id);

            if ($requestStep) {
                // 更新対象：instructionを更新
                $step->update(['instruction' => $requestStep['instruction'], 'order' => $requestStep['order'] ?? 0]);

                // 画像の変更処理
                $newImageId = $requestStep['imageId'] ?? null;
                $currentImageId = $step->images->first()?->id;

                // 画像が変更された場合（null/空文字列も含む）
                if ($newImageId !== $currentImageId) {
                    // 既存の画像を削除リストに追加
                    if ($step->images->isNotEmpty()) {
                        $imagesToDelete = array_merge($imagesToDelete, $step->images->pluck('id')->toArray());
                        // 既存の画像との紐づけを解除（重要）
                        $step->images()->detach();
                    }
                    // 新しい画像を紐づけ（空でない場合のみ）
                    if ($newImageId) {
                        $this->attachStepImage($step, $newImageId, $group);
                    }
                }
            } else {
                // 削除対象に追加（画像はあとでまとめて削除する）
                if ($step->images->isNotEmpty()) {
                    $imagesToDelete = array_merge($imagesToDelete, $step->images->pluck('id')->toArray());
                }
                // 手順を削除
                $step->delete();
            }
        }

        // 新規手順を作成
        foreach ($requestSteps as $index => $requestStep) {
            if (empty($requestStep['id'])) {
                $step = RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'instruction' => $requestStep['instruction'],
                    'order' => $requestStep['order'] ?? ($index + 1)
                ]);

                // 画像を紐づけ
                $imageId = $requestStep['imageId'] ?? null;
                if ($imageId) {
                    $this->attachStepImage($step, $imageId, $group);
                }
            }
        }

        // 画像の紐づけを解除
        if (!empty($imagesToDelete)) {
            $this->imageService->deleteImages(array_unique($imagesToDelete), $recipe->id, $group);
        }
    }

    /**
     * 手順に画像を紐づける
     * 注: 画像の存在確認は呼び出し元で事前に行うこと（updateStepsでは533行目で一括検証済み）
     */
    private function attachStepImage(RecipeStep $step, string $imageId, Group $group): void
    {
        $step->images()->attach($imageId, [
            'group_id' => $group->id,
            'related_model' => RecipeStep::class,
            'image_type' => 'image',
            'order' => 0
        ]);
    }
}
