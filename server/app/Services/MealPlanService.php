<?php

namespace App\Services;

use App\Models\CourseType;
use App\Models\Group;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MealPlanService extends AbstractDomainService
{
    protected MealTypeService $mealTypeService;
    protected RecipeService $recipeService;
    protected CourseTypeService $courseTypeService;
    protected ImageService $imageService;

    public function __construct(MealTypeService $mealTypeService, RecipeService $recipeService, CourseTypeService $courseTypeService, ImageService $imageService)
    {
        $this->mealTypeService = $mealTypeService;
        $this->recipeService = $recipeService;
        $this->courseTypeService = $courseTypeService;
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
        return ['id', 'date', 'meal_type_id'];
    }

    protected function getWithColumns(): array
    {
        return ['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.ingredients'];
    }

    protected function getGroupBy(): string | null
    {
        return 'date';
    }

    protected function formatIndexResponse(Model|Collection $items): array
    {
        // 型チェック
        $this->typeCheck($items, Collection::class);
        $this->typeCheckCollection($items, MealPlan::class);

        return [
            'date' => $items->first()->date,
            'mealPlans' => $items->map(function ($mealPlan) {
                return [
                    'id' => $mealPlan->id,
                    'date' => $mealPlan->date,
                    'category' => [
                        'id' => $mealPlan->mealType->id,
                        'name' => $mealPlan->mealType->name,
                        'colorId' => $mealPlan->mealType->color_id,
                    ],
                    'menu' => $this->formatMenu($mealPlan->recipes)
                ];
            })
        ];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => [
                'id' => $item->mealType->id,
                'name' => $item->mealType->name,
                'colorId' => $item->mealType->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    protected function formatShowResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => [
                'id' => $item->mealType->id,
                'name' => $item->mealType->name,
                'colorId' => $item->mealType->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealPlan::class);

        return [
            'id' => $item->id,
            'date' => $item->date,
            'category' => [
                'id' => $item->mealType->id,
                'name' => $item->mealType->name,
                'colorId' => $item->mealType->color_id,
            ],
            'menu' => $this->formatMenu($item->recipes)
        ];
    }

    public function create(array $data, Group $group): array
    {
        return DB::transaction(function () use ($data, $group) {
            // 献立種別の存在チェック
            $mealType = $this->mealTypeService->findItemsByIds([$data['mealTypeId']], $group);

            // 献立を作成
            $mealPlan = MealPlan::create([
                'group_id' => $group->id,
                'meal_type_id' => $mealType->first()->id,
                'date' => $data['date'],
            ]);

            // 献立・料理・コース種別を紐づけ
            if (!empty($data['menu'])) {
                $this->syncRecipes($mealPlan, $data['menu'], $group);
            }

            return $this->formatStoreResponse($mealPlan);
        });
    }

    public function update(string $id, array $data, Group $group): array
    {
        return DB::transaction(function () use ($id, $data, $group) {
            //更新対象を取得
            $mealPlan = $this->findItemsByIds([$id], $group)->first();

            // 献立種別の存在チェック
            $mealType = $this->mealTypeService->findItemsByIds([$data['mealTypeId']], $group);

            // 献立を更新
            $mealPlan->update([
                'group_id' => $group->id,
                'meal_type_id' => $mealType->first()->id,
                'date' => $data['date'],
            ]);

            // 献立・料理・コース種別を紐づけ
            if (!empty($data['menu'])) {
                $this->syncRecipes($mealPlan, $data['menu'], $group);
            }

            $item = $mealPlan->fresh(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.ingredients']);

            return $this->formatUpdateResponse($item);
        });
    }

    /**
     * 献立のメニュー情報をフォーマット
     */
    private function formatMenu($recipes): array
    {
        return $recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
            $courseType = CourseType::find($courseTypeId);
            return [
                'courseType' => [
                    'id' => $courseType->id,
                    'name' => $courseType->name
                ],
                'recipes' => $recipes->map(fn($recipe) => $this->recipeService->formatIndexResponse($recipe))
            ];
        })->values()->toArray();
    }

    private function syncRecipes(MealPlan $mealPlan, array $menu, Group $group): void
    {
        foreach ($menu as $item) {
            // レシピの存在チェック
            $recipes = $this->recipeService->findItemsByIds($item['recipeIds'], $group);
            // コース種別の存在チェック
            $courseTypes = $this->courseTypeService->findItemsByIds([$item['courseTypeId']], $group);

            // 紐づけ更新
            $attachData = collect($item['recipeIds'])->unique()->map(function ($recipeId) use ($mealPlan, $item) {
                return [
                    'meal_plan_id' => $mealPlan->id,
                    'recipe_id' => $recipeId,
                    'course_type_id' => $item['courseTypeId']
                ];
            });
            $mealPlan->recipes()->sync($attachData);
        }
    }
}
