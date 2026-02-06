<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\Meal;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MealPlanService extends AbstractDomainService
{
    protected MealCategoryService $mealCategoryService;
    protected RecipeService $recipeService;

    public function __construct(MealCategoryService $mealCategoryService, RecipeService $recipeService)
    {
        $this->mealCategoryService = $mealCategoryService;
        $this->recipeService = $recipeService;
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
        return ['meals.mealCategory.color', 'meals.recipes.categories', 'meals.recipes.thumbnails'];
    }

    protected function getGroupBy(): string | null
    {
        return 'date';
    }

    /**
     * 献立一覧を取得（指定した年・月の date 範囲でフィルタ）
     *
     * @param Group $group グループ
     * @param int $year 年
     * @param int $month 月（1-12）
     * @return array
     */
    public function indexForMonth(Group $group, int $year, int $month): array
    {
        return DB::transaction(function () use ($group, $year, $month) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));

            $query = $this->getGroupRelation($group)
                ->select($this->getSelectColumns())
                ->whereBetween('date', [$start, $end]);

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

            return $items->map(function ($item) {
                return $this->formatIndexResponse($item);
            })->toArray();
        });
    }

    protected function formatIndexResponse(Model|Collection $items): array
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
            'meals' => $this->formatMeals($allMeals),
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
            $categoryIds = array_unique(array_column($data['meals'], 'categoryId'));
            $this->mealCategoryService->findItemsByIds($categoryIds, $group);

            $allRecipeIds = array_unique(array_merge(...array_column($data['meals'], 'recipeIds')));
            $this->recipeService->findItemsByIds($allRecipeIds, $group);

            // 献立（1日）を作成
            $mealPlan = MealPlan::create([
                'group_id' => $group->id,
                'date' => $data['date'],
            ]);

            foreach ($data['meals'] as $mealData) {
                $meal = Meal::create([
                    'meal_plan_id' => $mealPlan->id,
                    'category_id' => $mealData['categoryId'],
                ]);
                $this->syncRecipes($meal, $mealData['recipeIds'], $group);
            }
        });
    }

    public function update(string $id, array $data, Group $group): MealPlan
    {
        return DB::transaction(function () use ($id, $data, $group) {
            $mealPlan = $this->findItemsByIds([$id], $group)->first();

            // 献立カテゴリ・レシピの存在チェック
            $categoryIds = array_unique(array_column($data['meals'], 'categoryId'));
            $this->mealCategoryService->findItemsByIds($categoryIds, $group);

            $allRecipeIds = array_unique(array_merge(...array_column($data['meals'], 'recipeIds')));
            $this->recipeService->findItemsByIds($allRecipeIds, $group);

            $existingMeals = $mealPlan->meals->keyBy('id');
            $idsToKeep = [];

            foreach ($data['meals'] as $mealData) {
                $mealId = $mealData['id'] ?? null;
                $meal = $mealId && $existingMeals->has($mealId) ? $existingMeals->get($mealId) : null;

                if ($meal) {
                    $meal->update(['category_id' => $mealData['categoryId']]);
                    $this->syncRecipes($meal, $mealData['recipeIds'], $group);
                    $idsToKeep[] = $meal->id;
                } else {
                    $newMeal = Meal::create([
                        'meal_plan_id' => $mealPlan->id,
                        'category_id' => $mealData['categoryId'],
                    ]);
                    $this->syncRecipes($newMeal, $mealData['recipeIds'], $group);
                    $idsToKeep[] = $newMeal->id;
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
     * @param Collection
     * @return array
     */
    private function formatMeals(Collection $meals): array
    {
        return $meals->map(function (Meal $meal) {
            return [
                'id' => $meal->id,
                'category' => $this->formatCategory($meal->mealCategory),
                'recipes' => $this->formatRecipes($meal->recipes),
            ];
        })->values()->toArray();
    }

    /**
     * 献立カテゴリをフォーマット
     * @param MealCategory $category
     * @return array
     */
    private function formatCategory($category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'colorCodeHex' => $category->color->color_code_hex,
            'order' => $category->order,
        ];
    }
    /**
     * レシピ一覧をフォーマット
     * @param Collection $recipes
     * @return array
     */
    private function formatRecipes(Collection $recipes): array
    {
        return $recipes->map(fn($recipe) => $this->recipeService->formatRecipeListItem($recipe))->values()->toArray();
    }

    /**
     * 献立にレシピを同期
     * @param Meal $meal
     * @param array $recipeIds
     * @param Group $group
     * @return void
     */
    private function syncRecipes(Meal $meal, array $recipeIds, Group $group): void
    {
        $recipeIds = array_values(array_unique($recipeIds));

        if (empty($recipeIds)) {
            $meal->recipes()->sync([]);
            return;
        }

        $this->recipeService->findItemsByIds($recipeIds, $group);
        $meal->recipes()->sync($recipeIds);
    }
}
