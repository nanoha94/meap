"use client";
import { useForm } from 'react-hook-form';

import { EDIT_MODE, EditMode } from '@/constants';
import { IMealPlan, IPostPutMealPlanRequest } from '@/types';
import { MealPlanEditFormData } from '../types';
import { useMealPlanApi } from './useMealPlanApi';
import { useMealStore } from './useMealStores';

export const useMealPlanEditForm = (date: string, fetchMealPlan?: IMealPlan) => {
    const { mealCategories } = useMealStore();
    const methods = useForm<MealPlanEditFormData>({
        defaultValues: {
            id: fetchMealPlan?.id ?? '',
            meals: mealCategories.map((category) =>
                fetchMealPlan?.meals.find((m) => m.category.id === category.id) ?? {
                    id: '',
                    category: category,
                    recipes: [],
                },
            ),
        },
    });
    const { control, handleSubmit } = methods;
    const { storeMealPlan, updateMealPlan } = useMealPlanApi();
    const editMode: EditMode = fetchMealPlan
        ? EDIT_MODE.UPDATE
        : EDIT_MODE.CREATE;

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: MealPlanEditFormData) => {
        const sendData: IPostPutMealPlanRequest = {
            date: date,
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
