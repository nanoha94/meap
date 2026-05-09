import React from "react";
import { Dayjs } from "dayjs";
import { useForm, useWatch } from "react-hook-form";

import { useDialog } from "@/hooks";
import { formatIngredient } from "@/models/ingredient";
import { MealPlanFilterFormData, useMealPlanApi } from "@/models/meal";
import { IMealCategory, IMealPlan } from "@/types";
import { ShoppingItemBulkCreateFormData } from "../types";
import { useShoppingItemApi } from "./useShoppingItemApi";
import { useShoppingStore } from "./useShoppingStores";

function isItemChecked(
    items: ShoppingItemBulkCreateFormData["items"],
    checkedName: string,
    recipe: { id: string; name: string },
    mealId: string,
) {
    return items.some(
        (v) =>
            v.name === checkedName &&
            v.mealId === mealId &&
            v.tags.some((tag) => tag.name === recipe.name && tag.id === recipe.id),
    );
}

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

    const searchMealPlans = React.useCallback(
        async (options: MealPlanFilterFormData) => {
            const data = await fetchMealPlans(options);
            setMealPlans(data ?? []);
        },
        [fetchMealPlans],
    );

    const updateDateList = React.useCallback((dates: Dayjs[]) => {
        setDateList(dates);
    }, []);

    const { control, handleSubmit, setValue } = useForm<ShoppingItemBulkCreateFormData>({
        defaultValues: {
            categoryId: categories.find(v => v.isDefault)?.id,
            items: [],
        },
    });
    const watchedItems = useWatch({ control, name: "items" });

    /**
   * 送信ボタンの無効化判定
   * アイテムが選択されていない場合は送信ボタンを無効化
   */
    const isDisabledSendButton = React.useMemo(() => {
        return (watchedItems ?? []).length <= 0;
    }, [watchedItems]);

    const isChecked = React.useCallback(
        (checkedName: string, recipe: { id: string; name: string }, mealId: string) =>
            isItemChecked(watchedItems ?? [], checkedName, recipe, mealId),
        [watchedItems],
    );

    const handleChange = React.useCallback(
        (checkedName: string, recipe: { id: string; name: string }, mealId: string) => {
            const current = watchedItems ?? [];
            if (isItemChecked(current, checkedName, recipe, mealId)) {
                setValue(
                    "items",
                    current.filter(
                        (v) =>
                            !(
                                v.name === checkedName &&
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
                        name: checkedName,
                        mealId,
                        tags: [{ id: recipe.id, name: recipe.name }],
                    },
                ]);
            }
        },
        [setValue, watchedItems],
    );

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
                            mealId: meal.id,
                            tags: [{ id: meal.recipeId, name: meal.recipeName }],
                        })),
                ),
            ]);
        },
        [setValue, watchedItems],
    );

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
        storeShoppingItems(
            data.items.map((item, index) => ({
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
