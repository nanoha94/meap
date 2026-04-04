"use client";
import React from "react";
import { Trash2 } from "lucide-react";
import { rectSortingStrategy, SortableContext } from "@dnd-kit/sortable";
import { useDroppable } from "@dnd-kit/core";

import { MenuButton, RecipeSelect, EmptyButton, Sortable } from "@/components";
import { COLOR_VARIANT, TMP_ID_PREFIX } from "@/constants";
import { useDialog } from "@/hooks";
import { useRecipeApi } from "@/models/recipe";
import { ActionButton, IMealCategory, IMealPlanItem, IRecipeListItem } from "@/types";
import RecipeCard from "../RecipeCard";

interface Props {
    mealCategory: IMealCategory;
    mealPlanItems: IMealPlanItem[];
    addItem: (item: IMealPlanItem) => void;
    deleteItem: (item: IMealPlanItem) => void;
}

/**
 * 献立カードフィールドに表示する料理リストアイテムに変換
 * @param item 献立カードフィールドに表示する料理リストアイテム
 * @returns 献立カードフィールドに表示する料理リストアイテム
 */
const mealPlanItemToRecipeListItem = (item: IMealPlanItem): IRecipeListItem => ({
    id: item.recipeId,
    name: item.recipeName,
    categories: [],
    thumbnail: item.recipeThumbnail,
    lastPlannedDate: null,
});


/**
 * 献立カードフィールド
 */
const MealCardField = ({ mealCategory, mealPlanItems, addItem, deleteItem }: Props) => {
    const { fetchRecipes } = useRecipeApi();
    const { openDialog, closeDialog } = useDialog();
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: mealCategory.id,
    });
    const prefix = TMP_ID_PREFIX.MEAL_PLAN_ITEM;

    /**
    * メニューボタン押下時に開くアクションボタン設定
    */
    const actionButtonConfigs: ActionButton[] = [
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () => {
                // フォームから削除する
                mealPlanItems.forEach(item => {
                    deleteItem(item);
                });
            },
            color: COLOR_VARIANT.ALERT,
            disabled: mealPlanItems.length === 0,
        },
    ];

    /** フォーム上の献立（ダイアログを開いた時点の選択と一致。確定まで mealPlanItems は変わらない） */
    const initialRecipesFromMealPlan: IRecipeListItem[] = React.useMemo(
        () => mealPlanItems.map(mealPlanItemToRecipeListItem),
        [mealPlanItems],
    );

    /**
     * 選択された料理をFieldに反映する
     */
    const SaveSelectedItems = React.useCallback((selectedItems: IRecipeListItem[]) => {
        const selectedIds = new Set(selectedItems.map((r) => r.id));
        const existingRecipeIds = new Set(mealPlanItems.map((i) => i.recipeId));

        mealPlanItems.forEach((item) => {
            if (!selectedIds.has(item.recipeId)) {
                deleteItem(item);
            }
        });

        let nextOrder = mealPlanItems.filter((i) => selectedIds.has(i.recipeId)).length;
        let addIndex = 0;
        selectedItems.forEach((recipe) => {
            // 重複しないように追加
            if (!existingRecipeIds.has(recipe.id)) {
                addItem({
                    id: `${prefix}${Date.now()}-${addIndex}`,
                    categoryId: mealCategory.id,
                    order: nextOrder,
                    recipeId: recipe.id,
                    recipeName: recipe.name,
                    recipeThumbnail: recipe.thumbnail,
                    recipeOrder: nextOrder,
                });
                nextOrder++;
                addIndex++;
            }
        });
        closeDialog(false);
    }, [mealPlanItems, mealCategory, addItem, deleteItem, closeDialog, prefix]);

    return (
        <div className="pl-3 pr-6 pt-2 pb-4 md:px-5 md:py-4 flex flex-col gap-y-5 bg-white md:rounded shadow-card">
            <div className="flex items-center justify-between">
                <div
                    className="relative pl-4 text-xl before:content-[''] before:absolute before:top-1/2 before:left-0 before:translate-y-[-50%] before:block before:w-1 before:h-5/6 before:bg-[var(--category-color)] before:rounded-full"
                    style={{ ["--category-color" as string]: mealCategory.colorCodeHex }}
                >
                    {mealCategory.name}
                </div>
                {actionButtonConfigs.length > 0 && (
                    <MenuButton actionButtons={actionButtonConfigs} placement="top-right" />
                )}
            </div>
            <SortableContext
                items={mealPlanItems.map(item => item.recipeId)}
                id={mealCategory.id}
                strategy={rectSortingStrategy}>
                <div ref={setDroppableNodeRef} className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                    {mealPlanItems.map((field) => (
                        <Sortable key={field.recipeId} id={field.recipeId}>
                            <RecipeCard
                                recipe={field}
                                isGrippable={true}
                                hasDeleteButton={true}
                                onDelete={() => { deleteItem(field); }}
                            />
                        </Sortable>
                    ))}
                    <EmptyButton type="button" className="ml-8 !w-[calc(100%-32px)] !h-auto aspect-[4/3]" onClick={async () => {
                        const result = await fetchRecipes();
                        openDialog({
                            title: '料理を検索',
                            children: <RecipeSelect
                                initFetchedRecipes={result}
                                defaultItems={initialRecipesFromMealPlan}
                                onSave={SaveSelectedItems}
                            />,

                            maxWidth: 1000,
                        });
                    }} />
                </div>
            </SortableContext>
        </div>
    );
};

export default MealCardField;
