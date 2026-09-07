<?php

namespace App\Support;

/**
 * API 入力検証の件数・文字列長上限。
 * DoS 耐性のため bulk 系・ネスト配列・文字列フィールドに適用する。
 */
final class ValidationLimits
{
    /** 買い物アイテム等の一括 data / ids 件数上限 */
    public const BULK_ITEM_DATA_MAX = 500;

    /** カテゴリ等の一括 data / ids 件数上限 */
    public const BULK_CATEGORY_DATA_MAX = 100;

    /** 買い物アイテム 1 件あたりのタグ数上限 */
    public const SHOPPING_ITEM_TAGS_MAX = 50;

    /** レシピの categoryIds 件数上限 */
    public const RECIPE_CATEGORY_IDS_MAX = 50;

    /** レシピの ingredientCategories 件数上限 */
    public const RECIPE_INGREDIENT_CATEGORIES_MAX = 100;

    /** レシピの ingredients 件数上限 */
    public const RECIPE_INGREDIENTS_MAX = 100;

    /** レシピの steps 件数上限 */
    public const RECIPE_STEPS_MAX = 100;

    /** 献立 1 日あたりの meals 件数上限 */
    public const MEAL_PLAN_MEALS_MAX = 20;

    /** 献立 1 食あたりの recipes 件数上限 */
    public const MEAL_PLAN_RECIPES_MAX = 100;

    /** 献立一覧取得の date_from〜date_to 期間上限（両端日を含む日数） */
    public const MEAL_PLAN_INDEX_DATE_RANGE_MAX_DAYS = 366;

    /** 名前・手順・quantityDisplay 等の基本文字列上限 */
    public const STRING_MAX = 255;
}
