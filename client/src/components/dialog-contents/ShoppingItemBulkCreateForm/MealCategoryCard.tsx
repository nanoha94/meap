"use client";

import React from "react";
import { Check, X } from "lucide-react";

import { TextButton } from "@/components";
import { CheckboxField } from "@/components/form-fields";
import { BUTTON_SIZE, BUTTON_TYPE, COLOR_VARIANT } from "@/constants";
import { formatIngredient } from "@/models/ingredient";
import { ShoppingItemBulkCreateFormItem } from "@/models/shopping/types";
import { IMealCategory, IMealPlan } from "@/types";

export type MealCategoryCardProps = {
    mealPlan: IMealPlan;
    mealCategory: IMealCategory;
    isChecked: (
        name: string,
        recipe: { id: string; name: string },
        mealId: string,
    ) => boolean;
    handleChange: (
        name: string,
        ingredient: ShoppingItemBulkCreateFormItem["ingredient"],
        recipe: { id: string; name: string },
        mealId: string,
    ) => void;
    handleSelectAll: (mealPlan: IMealPlan, mealCategory: IMealCategory) => void;
    handleUnselectAll: (mealPlan: IMealPlan, mealCategory: IMealCategory) => void;
};

const MealCategoryCard = ({
    mealPlan,
    mealCategory,
    isChecked,
    handleChange,
    handleSelectAll,
    handleUnselectAll,
}: MealCategoryCardProps) => {
    return (
        <div className="p-2.5 bg-white shadow-card flex flex-col gap-y-2">
            <div className="flex items-center justify-between">
                <div
                    className="relative pl-4 before:content-[''] before:absolute before:top-1/2 before:left-0 before:translate-y-[-50%] before:block before:w-1 before:h-5/6 before:bg-[var(--category-color)] before:rounded-full"
                    style={{ ["--category-color" as string]: mealCategory.colorCodeHex ?? "" }}
                >
                    {mealCategory.name}
                </div>
                <div className="flex gap-x-2">
                    <TextButton
                        type={BUTTON_TYPE.BUTTON}
                        size={BUTTON_SIZE.SMALL}
                        onClick={() => handleSelectAll(mealPlan, mealCategory)}
                    >
                        すべて選択
                        <Check size={16} />
                    </TextButton>
                    <TextButton
                        type={BUTTON_TYPE.BUTTON}
                        colorVariant={COLOR_VARIANT.ALERT}
                        size={BUTTON_SIZE.SMALL}
                        onClick={() => handleUnselectAll(mealPlan, mealCategory)}
                    >
                        すべて解除
                        <X size={16} />
                    </TextButton>
                </div>
            </div>
            {mealPlan.meals
                .filter((m) => m.categoryId === mealCategory.id)
                .map((meal, index) => (
                    <React.Fragment key={`${meal.id}-${index}`}>
                        <div className="text-xs">{meal.recipeName}</div>
                        <div className="flex flex-wrap gap-y-2 gap-x-3">
                            {meal.ingredients?.map((ingredient, ingredientIndex) => (
                                <CheckboxField
                                    key={`${meal.id}-${index}-${ingredient.id}-${ingredientIndex}`}
                                    id={`checkbox-${meal.id}-${index}-${ingredient.id}-${ingredientIndex}`}
                                    checked={isChecked(
                                        formatIngredient(ingredient),
                                        { id: meal.recipeId, name: meal.recipeName },
                                        meal.id,
                                    )}
                                    onChange={() =>
                                        handleChange(
                                            formatIngredient(ingredient),
                                            {
                                                name: ingredient.name,
                                                quantity: ingredient.quantity,
                                                quantityDisplay: ingredient.quantityDisplay,
                                                unit: ingredient.unit,
                                            },
                                            { id: meal.recipeId, name: meal.recipeName },
                                            meal.id,
                                        )
                                    }
                                    label={formatIngredient(ingredient)}
                                />
                            ))}
                        </div>
                    </React.Fragment>
                ))}
        </div>
    );
};

export default MealCategoryCard;
