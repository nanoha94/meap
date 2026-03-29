"use client";

import React from "react";
import { Dayjs } from "dayjs";

import { getDayOfWeekTextColor } from "@/constants";
import { IMealCategory, IMealPlan } from "@/types";

import MealCategoryCard from "./MealCategoryCard";

export type DayMealPlanSectionProps = {
    date: Dayjs;
    mealPlan: IMealPlan | undefined;
    mealCategories: IMealCategory[];
    isChecked: (checkedName: string, recipe: { id: string; name: string }) => boolean;
    handleChange: (checkedName: string, recipe: { id: string; name: string }) => void;
    handleSelectAll: (mealPlan: IMealPlan, mealCategory: IMealCategory) => void;
    handleUnselectAll: (mealPlan: IMealPlan, mealCategory: IMealCategory) => void;
};

const DayMealPlanSection = ({
    date,
    mealPlan,
    mealCategories,
    isChecked,
    handleChange,
    handleSelectAll,
    handleUnselectAll,
}: DayMealPlanSectionProps) => {
    if (!mealPlan) {
        return (
            <div className="px-3 flex flex-col gap-y-3">
                <div className={getDayOfWeekTextColor(date.day())}>
                    {date.format("MM/DD")}
                    <span className="ml-1 text-xs">{date.locale("ja").format("(ddd)")}</span>
                </div>
                <div>献立がまだ登録されていません。</div>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-y-3">
            <div className={`px-3 ${getDayOfWeekTextColor(date.day())}`}>
                {date.format("MM/DD")}
                <span className="ml-1 text-xs">{date.locale("ja").format("(ddd)")}</span>
            </div>
            {mealCategories
                .filter((c) => mealPlan.meals.some((m) => m.categoryId === c.id))
                .map((mealCategory) => (
                    <MealCategoryCard
                        key={mealCategory.id}
                        mealPlan={mealPlan}
                        mealCategory={mealCategory}
                        isChecked={isChecked}
                        handleChange={handleChange}
                        handleSelectAll={handleSelectAll}
                        handleUnselectAll={handleUnselectAll}
                    />
                ))}
        </div>
    );
};

export default DayMealPlanSection;
