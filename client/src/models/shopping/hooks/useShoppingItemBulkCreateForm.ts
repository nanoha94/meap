import React from "react";
import { Dayjs } from "dayjs";
import { useForm, useWatch } from "react-hook-form";

import { useDialog } from "@/hooks";
import { formatIngredient } from "@/models/ingredient";
import { MealPlanFilterFormData, useMealPlanApi } from "@/models/meal";
import { IIngredientItem, IMealCategory, IMealPlan } from "@/types";
import { formatSummedQuantityDisplay, sumQuantities } from "@/utils";
import {
    ShoppingItemBulkCreateFormData,
    ShoppingItemBulkCreateFormItem,
} from "../types";
import { useShoppingItemApi } from "./useShoppingItemApi";
import { useShoppingStore } from "./useShoppingStores";

type BulkCreateIngredient = ShoppingItemBulkCreateFormItem["ingredient"];

const pickBulkCreateIngredient = (
    ingredient: IIngredientItem,
): BulkCreateIngredient => ({
    name: ingredient.name,
    quantity: ingredient.quantity,
    quantityDisplay: ingredient.quantityDisplay,
    unit: ingredient.unit,
});

/**
 * マージキーを生成する
 * @param ingredient 材料
 * @returns マージキー
 */
const getMergeKey = (ingredient: BulkCreateIngredient): string =>
    `${ingredient.name}\0${ingredient.unit?.id ?? ""}`;

/**
 * タグを重複排除する
 * @param tags タグのリスト
 * @returns 重複排除されたタグのリスト
 */
const dedupeTags = (
    tags: ShoppingItemBulkCreateFormItem["tags"],
): ShoppingItemBulkCreateFormItem["tags"] => {
    const seen = new Set<string>();

    return tags.filter((tag) => {
        if (seen.has(tag.name)) {
            return false;
        }

        seen.add(tag.name);
        return true;
    });
};

/**
 * アイテムをマージする
 * @param items アイテムのリスト
 * @returns マージされたアイテムリスト
 */
const mergeBulkCreateItems = (
    items: ShoppingItemBulkCreateFormItem[],
): Array<{ name: string; tags: ShoppingItemBulkCreateFormItem["tags"] }> => {
    const groups = new Map<string, ShoppingItemBulkCreateFormItem[]>();

    for (const item of items) {
        const key = getMergeKey(item.ingredient);
        const group = groups.get(key) ?? [];
        group.push(item);
        groups.set(key, group);
    }

    return Array.from(groups.values()).map((groupItems) => {
        const baseIngredient = groupItems[0].ingredient;
        const allCanSum = groupItems.every((item) =>
            item.ingredient.unit?.requiresQuantity === true && item.ingredient.quantity != null
        );

        const mergedIngredient: BulkCreateIngredient = allCanSum
            ? (() => {
                const sum = sumQuantities(
                    groupItems.map((item) => item.ingredient.quantity!),
                );

                return {
                    ...baseIngredient,
                    quantity: sum,
                    quantityDisplay: formatSummedQuantityDisplay(
                        sum,
                        groupItems.map((item) => ({
                            quantityDisplay: item.ingredient.quantityDisplay,
                        })),
                    ),
                };
            })()
            : {
                ...baseIngredient,
                quantity: null,
                quantityDisplay: null,
            };

        return {
            name: formatIngredient(mergedIngredient as IIngredientItem),
            tags: dedupeTags(groupItems.flatMap((item) => item.tags)),
        };
    });
};

/**
 * アイテムが選択されているかどうかを判定する
 * @param items アイテムのリスト
 * @param name アイテムの名前
 * @param recipe レシピ
 * @param mealId 献立ID
 * @returns アイテムが選択されているかどうか
 */
function isItemChecked(
    items: ShoppingItemBulkCreateFormData["items"],
    name: string,
    recipe: { id: string; name: string },
    mealId: string,
) {
    return items.some(
        (v) =>
            v.name === name &&
            v.mealId === mealId &&
            v.tags.some((tag) => tag.name === recipe.name && tag.id === recipe.id),
    );
}

// ------------------------------------------------------------

export const useShoppingItemBulkCreateForm = () => {
    // store
    const categories = useShoppingStore(state => state.categories);
    const items = useShoppingStore(state => state.items);

    // hook
    const { closeDialog } = useDialog();
    const { fetchMealPlans } = useMealPlanApi();
    const { storeShoppingItems } = useShoppingItemApi();
    const [mealPlans, setMealPlans] = React.useState<IMealPlan[]>([]);
    const [dateList, setDateList] = React.useState<Dayjs[]>([]);

    const { control, handleSubmit, setValue } = useForm<ShoppingItemBulkCreateFormData>({
        defaultValues: {
            categoryId: categories.find(v => v.isDefault)?.id,
            items: [],
        },
    });
    const watchedItems = useWatch({ control, name: "items" });

    /**
     * 献立プランを検索する
     * @param options 検索オプション
     */
    const searchMealPlans = React.useCallback(
        async (options: MealPlanFilterFormData) => {
            const data = await fetchMealPlans(options);
            setMealPlans(data ?? []);
        },
        [fetchMealPlans],
    );

    /**
     * 日付リストを更新する
     * @param dates 日付のリスト
     */
    const updateDateList = React.useCallback((dates: Dayjs[]) => {
        setDateList(dates);
    }, []);


    /**
   * 送信ボタンの無効化判定
   * アイテムが選択されていない場合は送信ボタンを無効化
   */
    const isDisabledSendButton = React.useMemo(() => {
        return (watchedItems ?? []).length <= 0;
    }, [watchedItems]);

    const isChecked = React.useCallback(
        (name: string, recipe: { id: string; name: string }, mealId: string) =>
            isItemChecked(watchedItems ?? [], name, recipe, mealId),
        [watchedItems],
    );

    /**
     * アイテムを変更する
     * @param name アイテムの名前
     * @param ingredient 材料
     * @param recipe レシピ
     * @param mealId 献立ID
     * @returns アイテムを変更する
     */
    const handleChange = React.useCallback(
        (
            name: string,
            ingredient: BulkCreateIngredient,
            recipe: { id: string; name: string },
            mealId: string,
        ) => {
            const current = watchedItems ?? [];
            if (isItemChecked(current, name, recipe, mealId)) {
                setValue(
                    "items",
                    current.filter(
                        (v) =>
                            !(
                                v.name === name &&
                                v.mealId === mealId &&
                                v.tags.some(
                                    (tag) => tag.name === recipe.name && tag.id === recipe.id,
                                )
                            ),
                    ),
                );
            } else {
                setValue("items", [
                    ...current,
                    {
                        name,
                        ingredient,
                        mealId,
                        tags: [{ id: recipe.id, name: recipe.name }],
                    },
                ]);
            }
        },
        [setValue, watchedItems],
    );

    /**
     * すべての項目を選択する
     * @param mealPlan 献立プラン
     * @param mealCategory 献立カテゴリ
     */
    const handleSelectAll = React.useCallback(
        (mealPlan: IMealPlan, mealCategory: IMealCategory) => {
            const current = watchedItems ?? [];
            const mealsInCategory = mealPlan.meals.filter((m) => m.categoryId === mealCategory.id);
            setValue("items", [
                ...current,
                ...mealsInCategory.flatMap((meal) =>
                    (meal.ingredients ?? [])
                        .filter(
                            (ingredient) =>
                                !isItemChecked(current, formatIngredient(ingredient), {
                                    id: meal.recipeId,
                                    name: meal.recipeName,
                                }, meal.id),
                        )
                        .map((ingredient) => ({
                            name: formatIngredient(ingredient),
                            ingredient: pickBulkCreateIngredient(ingredient),
                            mealId: meal.id,
                            tags: [{ id: meal.recipeId, name: meal.recipeName }],
                        })),
                ),
            ]);
        },
        [setValue, watchedItems],
    );

    /**
     * すべての項目を選択解除する
     * @param mealPlan 献立プラン
     * @param mealCategory 献立カテゴリ
     */
    const handleUnselectAll = React.useCallback(
        (mealPlan: IMealPlan, mealCategory: IMealCategory) => {
            const current = watchedItems ?? [];
            const mealsInCategory = mealPlan.meals.filter((m) => m.categoryId === mealCategory.id);
            setValue(
                "items",
                current.filter(
                    (item) =>
                        !mealsInCategory.some((meal) =>
                            (meal.ingredients ?? []).some(
                                (ingredient) =>
                                    item.name === formatIngredient(ingredient) &&
                                    item.mealId === meal.id &&
                                    item.tags.some(
                                        (tag) =>
                                            tag.id === meal.recipeId &&
                                            tag.name === meal.recipeName,
                                    ),
                            ),
                        ),
                ),
            );
        },
        [setValue, watchedItems],
    );

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: ShoppingItemBulkCreateFormData) => {
        // 献立UIではタグに recipeId を id として保持しているが、API の tags.id は ShoppingTag の UUID のみ有効。
        // レシピIDを送ると findOrCreateIds が「存在しないID」として 500 になるため、名前のみ送る。
        const baseOrder = items.length;
        const mergedItems = mergeBulkCreateItems(data.items);

        storeShoppingItems(
            mergedItems.map((item, index) => ({
                name: item.name,
                categoryId: data.categoryId,
                tags: item.tags.map((tag) => ({ name: tag.name })),
                order: baseOrder + index,
                isPinned: false,
                isChecked: false,
            })),
        );
        closeDialog(false);
    };

    return {
        control,
        isDisabledSendButton,
        mealPlans,
        dateList,
        isChecked,
        handleChange,
        handleSelectAll,
        handleUnselectAll,
        onSubmit: handleSubmit(onSubmit),
        searchMealPlans,
        updateDateList,
    };
};
