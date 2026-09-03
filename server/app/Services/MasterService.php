<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class MasterService
{
    public function __construct(
        private UserService $userService,
        private RecipeCategoryService $recipeCategoryService,
        private IngredientUnitService $ingredientUnitService,
        private MealCategoryService $mealCategoryService,
        private ShoppingCategoryService $shoppingCategoryService,
        private ShoppingTagService $shoppingTagService
    ) {}

    /**
     * マスター API レスポンスのキャッシュ TTL（分）。
     *
     * レスポンス内のアバター画像 src には ImageService::formatImage() 経由の署名付き URL が含まれる。
     * 不変条件: この値 < config('filesystems.signed_url_ttl') を必ず守ること。
     * 逆転するとキャッシュから期限切れ URL が返る。TTL を延ばす場合は formatImage() をキャッシュ外に出す。
     */
    private const CACHE_TTL_MINUTES = 30;

    public function index(Group $group): array
    {
        return Cache::remember(
            "master:{$group->id}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => [
                'users' => $this->userService->index($group),
                'recipeCategories' => $this->recipeCategoryService->index($group),
                'ingredientUnits' => $this->ingredientUnitService->index($group),
                'mealCategories' => $this->mealCategoryService->index($group),
                'shoppingCategories' => $this->shoppingCategoryService->index($group),
                'shoppingTags' => $this->shoppingTagService->index($group),
            ]
        );
    }

    /**
     * グループのマスターAPIレスポンスキャッシュを破棄する（マスター系データ更新時に呼ぶ）
     */
    public static function forgetGroupCache(Group|int|string $group): void
    {
        $id = $group instanceof Group ? $group->id : $group;
        Cache::forget("master:{$id}");
    }
}
