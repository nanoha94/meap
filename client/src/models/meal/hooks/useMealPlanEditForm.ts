"use client";
import { useForm } from 'react-hook-form';

import { EDIT_MODE, EditMode } from '@/constants';
import { IMealPlan, IPostPutMealPlanRequest, IRecipeListItem } from '@/types';
import { MealPlanEditFormData } from '../types';
import { DEFAULT_MEAL_PLAN_EDIT_FORM_DATA } from '../constants';
import { useMealPlanApi } from './useMealPlanApi';


export const useMealPlanEditForm = (date: string, fetchMealPlans?: IMealPlan[]) => {
    const methods = useForm<MealPlanEditFormData>({
        defaultValues: { 
            ...DEFAULT_MEAL_PLAN_EDIT_FORM_DATA, 
            ...fetchMealPlans,
        },
    });
    const { control, handleSubmit } = methods;
    const { storeMealPlan, updateMealPlan } = useMealPlanApi();
    const editMode: EditMode = fetchMealPlans
        ? EDIT_MODE.UPDATE
        : EDIT_MODE.CREATE;

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: MealPlanEditFormData) => {
        const sendData: IPostPutMealPlanRequest = {
            date: date,
            mealCategoryId: data.mealCategory.id,
            recipeIds: data.recipes.map((v: IRecipeListItem) => v.id),
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
