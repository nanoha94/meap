"use client";
import { useForm } from 'react-hook-form';

import { EDIT_MODE, EditMode } from '@/constants';
import { IMealPlan, IPostPutMealPlanRequest } from '@/types';
import { MealPlanEditFormData } from '../types';
import { DEFAULT_MEAL_PLAN_EDIT_FORM_DATA } from '../constants';
import { useMealPlanApi } from './useMealPlanApi';


export const useMealPlanEditForm = (fetchMealPlan?: IMealPlan) => {
    const methods = useForm<MealPlanEditFormData>({
        defaultValues: { 
            ...DEFAULT_MEAL_PLAN_EDIT_FORM_DATA, 
            ...fetchMealPlan,
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
            date: data.date,
            mealCategoryId: data.mealCategoryId,
            recipeIds: data.recipeIds,
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
