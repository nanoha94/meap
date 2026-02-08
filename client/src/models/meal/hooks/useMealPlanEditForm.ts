"use client";
import React from 'react';
import { useForm } from 'react-hook-form';

import { EDIT_MODE, EditMode } from '@/constants';
import { IMealCategory, IMealPlan, IPostPutMealPlanRequest } from '@/types';
import { MealPlanEditFormData } from '../types';
import { useMealPlanApi } from './useMealPlanApi';
import { useMealStore } from './useMealStores';

/**
 * デフォルト値を取得
 * @param fetchMealPlan 献立表
 * @param mealCategories 献立カテゴリ
 * @returns 
 */
const getDefaultValues = (
    fetchMealPlan: IMealPlan | undefined,
    mealCategories: IMealCategory[],
): MealPlanEditFormData => ({
    id: fetchMealPlan?.id ?? '',
    meals: mealCategories.map((category) =>
        fetchMealPlan?.meals.find((m) => m.category.id === category.id) ?? {
            id: '',
            category: category,
            recipes: [],
        },
    ),
});

export const useMealPlanEditForm = (selectedDate: string, fetchMealPlan?: IMealPlan) => {
    const { mealCategories } = useMealStore();
    const methods = useForm<MealPlanEditFormData>({
        defaultValues: getDefaultValues(fetchMealPlan, mealCategories),
    });
    const { control, handleSubmit, reset } = methods;
    const { storeMealPlan, updateMealPlan } = useMealPlanApi();
    const editMode: EditMode = fetchMealPlan ? EDIT_MODE.UPDATE : EDIT_MODE.CREATE;

    // 非同期で fetchMealPlan が渡ってきたときにフォームを再設定（useFieldArray に反映される）
    React.useEffect(() => {
        reset(getDefaultValues(fetchMealPlan, mealCategories));
    }, [fetchMealPlan, mealCategories, reset]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: MealPlanEditFormData) => {
        const sendData: IPostPutMealPlanRequest = {
            date: selectedDate,
            meals: data.meals.filter(meal => meal.recipes.length > 0),
        };

        if (editMode === EDIT_MODE.CREATE) {
            storeMealPlan(sendData);
        } else {
            updateMealPlan(
                { ...sendData, id: data.id },
            );
        }
    };

    return {
        control,
        methods,
        editMode,
        onSubmit: handleSubmit(onSubmit),
    };
};
