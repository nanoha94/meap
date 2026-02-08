"use client";
import { MenuButton } from "@/components";
import EmptyButton from "@/components/EmptyButton";
import { colors } from "@/constants";
import { MealPlanEditFormData } from "@/models/meal/types";
import { ActionButton, IMealCategory, IRecipeListItem } from "@/types";
import { GripVertical } from "lucide-react";
import { Control, useFieldArray } from "react-hook-form";
import RecipeCard from "../RecipeCard";

interface MealCardFieldProps {
    control: Control<MealPlanEditFormData>;
    mealCategory: IMealCategory;
    mealIndex: number;
    actionButtonConfigs: ActionButton[];
}

/**
 * 献立カードフィールド
 */
const MealCardField = ({ control, mealCategory, mealIndex, actionButtonConfigs }: MealCardFieldProps) => {
    const { fields, remove } = useFieldArray({ control, name: `meals.${mealIndex}.recipes` });

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
            <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                {fields.map((field, index) => (
                    <div key={field.id} className="flex gap-x-2">
                        <GripVertical color={colors.gray.main} className="pt-1" />
                        <RecipeCard
                            recipe={field as IRecipeListItem}
                            hasDeleteButton={true}
                            onDelete={() => remove(index)}
                        />
                    </div>
                ))}
                <EmptyButton type="button" className="ml-8 !w-[calc(100%-32px)] !h-auto aspect-[4/3]" />
            </div>
        </div>
    );
};

export default MealCardField;
