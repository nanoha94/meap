"use client";
import React from "react";
import { Check } from "lucide-react";
import { rectSortingStrategy, SortableContext } from "@dnd-kit/sortable";
import { useDroppable } from "@dnd-kit/core";

import { HeaderTextButton, MenuButton, RecipeSelect, EmptyButton, Sortable } from "@/components";
import { BUTTON_TYPE, COLOR_VARIANT, TMP_ID_PREFIX } from "@/constants";
import { useRecipeApi } from "@/models/recipe";
import { ActionButton, IMealCategory, IMealPlanItem, IRecipeListItem } from "@/types";
import { useDialog } from "@/hooks";
import RecipeCard from "../RecipeCard";

interface Props {
    mealCategory: IMealCategory;
    recipes: IMealPlanItem[];
    actionButtonConfigs: ActionButton[];
    addItem: (item: IMealPlanItem) => void;
    deleteItem: (item: IMealPlanItem) => void;
}

/**
 * 献立カードフィールド
 */
const MealCardField = ({ mealCategory, recipes, actionButtonConfigs, addItem, deleteItem }: Props) => {
    const { fetchRecipes } = useRecipeApi();
    const { openDialog, closeDialog, updateCurrentDialogConfig } = useDialog();
    const [selectedRecipeInDialog, setSelectedRecipeInDialog] = React.useState<IRecipeListItem | null>(null);
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: mealCategory.id,
    });
    const prefix = TMP_ID_PREFIX.MEAL_PLAN_ITEM;

    /**
     * 確定ボタンの無効化判定
     */
    const dialogButtonDisabled = React.useMemo(() => selectedRecipeInDialog === null, [selectedRecipeInDialog]);

    /**
     * 確定ボタン押下時の処理（ダイアログヘッダーボタン）
     */
    const handleConfirm = React.useCallback(() => {
        if (selectedRecipeInDialog === null) return;
        addItem({
            id: `${prefix}${Date.now()}`,
            recipeId: selectedRecipeInDialog.id,
            name: selectedRecipeInDialog.name,
            thumbnail: selectedRecipeInDialog.thumbnail,
            categoryId: mealCategory.id,
            order: recipes.length,
        });
        closeDialog();
    }, [selectedRecipeInDialog, closeDialog]);

    // ダイアログ内の customButton / children は開いた時点の要素が store に保存されるため、
    // selectedRecipeInDialog が変わったら store の config を更新して Dialog を再レンダーさせる
    React.useEffect(() => {
        updateCurrentDialogConfig({
            customButton: (
                <HeaderTextButton type={BUTTON_TYPE.BUTTON} colorVariant={COLOR_VARIANT.SECONDARY} disabled={dialogButtonDisabled} onClick={handleConfirm}>
                    <Check size={20} strokeWidth={2} />
                    確定
                </HeaderTextButton>
            ),
            children: () => (
                <RecipeSelect
                    selectedRecipe={selectedRecipeInDialog}
                    disabledRecipes={recipes.map(item => item.recipeId)}
                    onSelectedRecipeChange={setSelectedRecipeInDialog}
                    onConfirm={handleConfirm}
                />
            ),
        });
    }, [selectedRecipeInDialog, dialogButtonDisabled, updateCurrentDialogConfig, handleConfirm]);

    return (
        <div className="pr-2 pl-3 pt-2 pb-4 md:px-5 md:py-4 flex flex-col gap-y-5 bg-white md:rounded shadow-card">
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
                items={recipes.map(item => item.recipeId)}
                id={mealCategory.id}
                strategy={rectSortingStrategy}>
                <div ref={setDroppableNodeRef} className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                    {recipes.map((field) => (
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
                        await fetchRecipes();
                        setSelectedRecipeInDialog(null);
                        openDialog({
                            title: '料理を検索',
                            customButton: <HeaderTextButton type={BUTTON_TYPE.BUTTON} colorVariant={COLOR_VARIANT.SECONDARY} disabled={dialogButtonDisabled} onClick={handleConfirm}>
                                <Check size={20} strokeWidth={2} />
                                確定
                            </HeaderTextButton>,
                            children: () => (
                                <RecipeSelect
                                    selectedRecipe={selectedRecipeInDialog}
                                    disabledRecipes={recipes.map(item => item.recipeId)}
                                    onSelectedRecipeChange={setSelectedRecipeInDialog}
                                    onConfirm={handleConfirm}
                                />
                            )
                        });
                    }} />
                </div>
            </SortableContext>
        </div>
    );
};

export default MealCardField;
