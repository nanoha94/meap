<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MealPlanService extends AbstractDomainService
{
    protected MealCategoryService $mealCategoryService;
    protected RecipeService $recipeService;
    protected ImageService $imageService;

    public function __construct(MealCategoryService $mealCategoryService, RecipeService $recipeService, ImageService $imageService)
    {
        $this->mealCategoryService = $mealCategoryService;
        $this->recipeService = $recipeService;
        $this->imageService = $imageService;
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.meal_plan');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->mealPlans();
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'date'];
    }

    protected function getWithColumns(): array
    {
        return [
            'meals.mealCategory.color',
            'meals.recipes.thumbnails',
            'meals.recipes.ingredients',
            'meals.recipes.ingredientUnits'
        ];
    }

    protected function getGroupBy(): string | null
    {
        return 'date';
    }

    protected function getCreateFields(): array
    {
        return ['category_id' => 'categoryId', 'order' => 'order'];
    }

    protected function getUpdateFields(): array
    {
        return ['category_id' => 'categoryId', 'order' => 'order'];
    }

    /**
     * 献立一覧を取得（指定した日付範囲でフィルタ）
     *
     * @param Group $group グループ
     * @param string $dateFrom 開始日（Y-m-d）
     * @param string $dateTo 終了日（Y-m-d）
     * @param bool $includeIngredients 食材を含めるか
     * @return array
     */
    public function indexForDateRange(Group $group, string $dateFrom, string $dateTo, bool $includeIngredients = false): array
    {
        return DB::transaction(function () use ($group, $dateFrom, $dateTo, $includeIngredients) {
            $query = $this->getGroupRelation($group)
                ->select($this->getSelectColumns())
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($this->getWithColumns()) {
                $query->with($this->getWithColumns());
            }

            if ($this->getOrderBy()) {
                $query->orderBy($this->getOrderBy());
            }

            $items = $query->get();

            if ($this->getGroupBy()) {
                $items = $items->groupBy($this->getGroupBy())->values();
            }

            return $items->map(function ($item) use ($includeIngredients, $group) {
                return $this->formatIndexResponse($item, $includeIngredients, $group);
            })->toArray();
        });
    }

    /**
     * 指定日付の献立を1件取得（同一日付に複数ある場合はマージして返す）
     *
     * @param Group $group グループ
     * @param string $date 日付（Y-m-d）
     * @return array show 形式（id, date, meals）
     * @throws HttpException 献立が存在しない場合
     */
    public function showByDate(string $date, Group $group): array
    {
        return DB::transaction(function () use ($group, $date) {
            $item = $this->getGroupRelation($group)
                ->where('date', $date)
                ->select($this->getSelectColumns());

            if ($this->getWithColumns()) {
                $item->with($this->getWithColumns());
            }

            if ($this->getOrderBy()) {
                $item->orderBy($this->getOrderBy());
            }

            $result = $item->first();

            if ($result === null) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => $this->getResourceName()])
                );
            }

            return $this->formatShowResponse($result);
        });
    }

    protected function formatIndexResponse(Model|Collection $items, bool $includeIngredients = false, ?Group $group = null): array
    {
        // 型チェック（groupBy により 1 日分の MealPlan の Collection が渡る）
        $this->typeCheck($items, Collection::class);
        $this->typeCheckCollection($items, MealPlan::class);

        $mealPlan = $items->first();
        // 同一日付に複数献立がある場合はその日の全 meals をマージする
        $allMeals = $items->flatMap(fn(MealPlan $mp) => $mp->meals);

        return [
            'id' => $mealPlan->id,
            'date' => $mealPlan->date,
            'meals' => $this->formatMeals($allMeals, $includeIngredients, $group),
        ];
    }

    protected function formatShowResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'meals' => $this->formatMeals($item->meals),
        ];
    }

    public function create(array $data, Group $group): void
    {
        DB::transaction(function () use ($data, $group) {
            // 献立カテゴリ・レシピの存在チェック
            $this->validateMealsData($data['meals'], $group);

            // 献立（1日）を作成
            $mealPlan = MealPlan::create([
                'group_id' => $group->id,
                'date' => $data['date'],
            ]);

            foreach ($data['meals'] as $mealData) {
                $this->createMeal($mealPlan, $mealData, $group);
            }
        });
    }

    public function update(string $id, array $data, Group $group): MealPlan
    {
        return DB::transaction(function () use ($id, $data, $group) {
            $mealPlan = $this->findItemsByIds([$id], $group)->first();
            // 献立カテゴリ・レシピの存在チェック
            $this->validateMealsData($data['meals'], $group);

            $existingMeals = $mealPlan->meals->keyBy('id');
            $idsToKeep = [];

            foreach ($data['meals'] as $mealData) {
                $mealId = $mealData['id'] ?? null;
                $meal = $mealId && $existingMeals->has($mealId) ? $existingMeals->get($mealId) : null;

                // 既存の献立がある場合は更新
                if ($meal) {
                    $updateData = [];
                    foreach ($this->getUpdateFields() as $field => $dataKey) {
                        $updateData[$field] = $mealData[$dataKey];
                    }
                    $meal->update($updateData);
                    $this->syncRecipes($meal, $mealData['recipes'], $group);
                    $idsToKeep[] = $meal->id;
                }
                // 既存の献立がない場合は作成
                else {
                    $idsToKeep[] = $this->createMeal($mealPlan, $mealData, $group)->id;
                }
            }

            $mealPlan->meals()->whereNotIn('id', $idsToKeep)->delete();

            return $mealPlan->fresh();
        });
    }

    /**
     * 献立に紐づく1食を削除する（献立は削除しない）
     *
     * @param string $mealPlanId 献立ID
     * @param string $mealId 削除する食事ID
     * @param Group $group グループ
     * @return Meal 削除された食事
     * @throws HttpException 献立または食事が見つからない場合
     */
    public function deleteMeal(string $mealPlanId, string $mealId, Group $group): Meal
    {
        return DB::transaction(function () use ($mealPlanId, $mealId, $group) {
            $mealPlan = $this->findItemsByIds([$mealPlanId], $group)->first();

            $meal = $mealPlan->meals()->where('id', $mealId)->first();

            if (!$meal) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.meal')])
                );
            }

            // 削除メッセージ用に献立・カテゴリをロード
            $meal->load(['mealPlan', 'mealCategory']);
            $meal->delete();

            return $meal;
        });
    }

    /**
     * 1食分の献立メニュー（meals）をフォーマット
     * @param Collection $meals
     * @param bool $includeIngredients 食材を含めるか
     * @param Group|null $group グループ（食材を含める場合に必要）
     * @return array
     */
    private function formatMeals(Collection $meals, bool $includeIngredients = false, ?Group $group = null): array
    {
        return $meals->sortBy('order')->values()->flatMap(function (Meal $meal) use ($includeIngredients, $group) {
            return array_map(function (array $recipeItem) use ($meal) {
                $recipeItem['id'] = $meal['id'];
                $recipeItem['categoryId'] = $meal->category_id;
                $recipeItem['order'] = $meal->order;

                return $recipeItem;
            }, $this->formatRecipes($meal->recipes, $includeIngredients, $group));
        })->values()->toArray();
    }

    /**
     * 献立用にレシピをフォーマット（MealPlanItem 形式: id, recipeId, recipeOrder, name, thumbnail）
     * @param Collection $recipes orderByPivot('order') 済みのコレクション
     * @param bool $includeIngredients 食材を含めるか
     * @param Group|null $group グループ（食材を含める場合に必要）
     * @return array
     */
    private function formatRecipes(Collection $recipes, bool $includeIngredients = false, ?Group $group = null): array
    {
        return $recipes->map(function (Recipe $recipe) use ($includeIngredients, $group) {
            $item = [
                'recipeId' => $recipe->id,
                'recipeName' => $recipe->name,
                'recipeThumbnail' => $this->imageService->formatImage($recipe->thumbnails->first()),
                'recipeOrder' => (int) ($recipe->pivot->order ?? 0),
            ];
            if ($includeIngredients && $group) {
                $item['ingredients'] = $this->recipeService->formatRecipeIngredients($recipe, $group);
            }

            return $item;
        })->values()->toArray();
    }

    /**
     * 献立の meals データの献立カテゴリ・レシピの存在チェック
     * @param array $meals リクエストの meals 配列
     * @param Group $group グループ
     * @return void
     */
    private function validateMealsData(array $meals, Group $group): void
    {
        // 献立カテゴリの存在チェック
        $categoryIds = array_unique(array_column($meals, 'categoryId'));
        $this->mealCategoryService->findItemsByIds($categoryIds, $group);

        // レシピの存在チェック（meals[].recipes[].id から集約）
        $allRecipes = array_merge(...array_map(fn(array $m) => $m['recipes'] ?? [], $meals));
        $allRecipeIds = array_unique(array_column($allRecipes, 'id'));
        $this->recipeService->findItemsByIds($allRecipeIds, $group);
    }

    /**
     * 献立に1食を追加して作成
     * @param MealPlan $mealPlan 献立
     * @param array $mealData リクエストの1食分データ（categoryId, order, recipes: [{ id, order }]）
     * @param Group $group グループ
     * @return Meal 作成した食事
     */
    private function createMeal(MealPlan $mealPlan, array $mealData, Group $group): Meal
    {
        $createData = ['meal_plan_id' => $mealPlan->id];
        foreach ($this->getCreateFields() as $field => $dataKey) {
            $createData[$field] = $mealData[$dataKey];
        }
        $meal = Meal::create($createData);
        $this->syncRecipes($meal, $mealData['recipes'], $group);

        return $meal;
    }

    /**
     * 献立にレシピを同期（pivot の order を保存）
     * @param Meal $meal
     * @param array $recipes 各要素は ['id' => string, 'order' => int]
     * @param Group $group
     * @return void
     */
    private function syncRecipes(Meal $meal, array $recipes, Group $group): void
    {
        $recipeIds = array_values(array_unique(array_column($recipes, 'id')));

        if (empty($recipeIds)) {
            $meal->recipes()->sync([]);
            return;
        }

        $this->recipeService->findItemsByIds($recipeIds, $group);

        $syncData = [];
        foreach ($recipes as $item) {
            $syncData[$item['id']] = ['order' => $item['order']];
        }
        $meal->recipes()->sync($syncData);
    }
}
