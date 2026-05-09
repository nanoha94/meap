/**
 * 一時的なIDプレフィックス
 * （id不要でリクエストできる要素に対して使用）
 */
export const TMP_ID_PREFIX = {
    // 献立アイテム
    MEAL_PLAN_ITEM: 'meap-meal-plan-item-',
    // レシピカテゴリー
    RECIPE_CATEGORY: 'meap-recipe-category-',
    // 手順
    RECIPE_STEP: 'meap-recipe-step-',
    // 食材アイテム
    INGREDIENT_ITEM: 'meap-ingredient-item-',
    // 食材カテゴリー
    INGREDIENT_CATEGORY: 'meap-ingredient-category-',
    // 買い物カテゴリ―
    SHOPPING_CATEGORY: 'meap-shopping-category-',
    // 画像
    IMAGE: 'meap-image-',
} as const;
