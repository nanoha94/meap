"use client";
import React from 'react';
import { useFieldArray, useForm, useWatch } from 'react-hook-form';

import { EDIT_MODE, EditMode, TMP_ID_PREFIX } from '@/constants';
import { IMealCategory, IMealPlan, IMealPlanItem, IPostPutMealPlanRequest } from '@/types';
import { MealPlanEditFormData } from '../types';
import { useMealPlanApi } from './useMealPlanApi';
import { useMealStore } from './useMealStores';

/**
 * デフォルト値を取得
 * @param fetchMealPlan 献立表
 * @param mealCategories 献立カテゴリ
 * @returns デフォルト値
 */
const getDefaultValues = (
    fetchMealPlan: IMealPlan | undefined,
    mealCategories: IMealCategory[],
): MealPlanEditFormData => {
    const meals = mealCategories.flatMap((category) => {
        const categoryMeals = (fetchMealPlan?.meals ?? [])
            .filter((m) => m.categoryId === category.id);
        return categoryMeals;
    });
    return {
        id: fetchMealPlan?.id ?? '',
        meals,
    };
};

const normalizeForCompare = (items: IMealPlanItem[]): Omit<IMealPlanItem, 'id'>[] =>
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    items.map(({ id, ...rest }) => rest);

export const useMealPlanEditForm = (selectedDate: string, fetchMealPlan?: IMealPlan) => {
    // store
    const mealCategories = useMealStore(state => state.mealCategories);

    // hook
    const methods = useForm<MealPlanEditFormData>({
        defaultValues: getDefaultValues(fetchMealPlan, mealCategories),
    });
    const { control, handleSubmit, reset } = methods;
    const mealsFieldArray = useFieldArray({ control, name: 'meals' });
    const watchedMeals = useWatch({ control, name: 'meals' });
    const { storeMealPlan, updateMealPlan } = useMealPlanApi();

    // state
    const editMode: EditMode = fetchMealPlan ? EDIT_MODE.UPDATE : EDIT_MODE.CREATE;

    // 非同期で fetchMealPlan が渡ってきたときにフォームを再設定（useFieldArray に反映される）
    React.useEffect(() => {
        reset(getDefaultValues(fetchMealPlan, mealCategories));
    }, [fetchMealPlan, mealCategories, reset]);

    /**
     * 送信ボタンの無効化判定
     * 献立が変更されていない場合は送信ボタンを無効化
     */
    const isDisabledSendButton = React.useMemo(() => {
        const current = (watchedMeals ?? []) as IMealPlanItem[];
        return JSON.stringify(normalizeForCompare(fetchMealPlan?.meals ?? [])) === JSON.stringify(normalizeForCompare(current));
    }, [fetchMealPlan?.meals, watchedMeals]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: MealPlanEditFormData) => {
        const filteredMeals = data.meals.filter(
            (meal) => meal.recipeId && meal.recipeId.length > 0,
        );

        // categoryId でグループ化し、各グループの recipeId を recipeIds 配列に集約
        const groupedByCategoryId = filteredMeals.reduce(
            (acc, meal) => {
                const key = meal.categoryId;
                if (!acc[key]) {
                    acc[key] = {
                        id: meal.id,
                        categoryId: meal.categoryId,
                        order: meal.order,
                        recipes: [],
                    };
                }
                acc[key].recipes.push({ id: meal.recipeId, order: acc[key].recipes.length });
                return acc;
            },
            {} as Record<
                string,
                {
                    id: string;
                    categoryId: string;
                    order: number;
                    recipes: { id: string, order: number }[]
                }
            >,
        );

        // 送信データを作成（id, dateを除く）
        const sendData: IPostPutMealPlanRequest = {
            meals: Object.values(groupedByCategoryId).map((v, idx) => ({
                id: v.id?.startsWith(TMP_ID_PREFIX.MEAL_PLAN_ITEM) ? '' : v.id,
                categoryId: v.categoryId,
                recipes: v.recipes,
                order: idx,
            })),
        };

        if (editMode === EDIT_MODE.CREATE) {
            storeMealPlan({ ...sendData, date: selectedDate });
        } else {
            updateMealPlan({ ...sendData, id: data.id });
        }
    };

    return {
        control,
        methods,
        editMode,
        isDisabledSendButton,
        onSubmit: handleSubmit(onSubmit),
        ...mealsFieldArray,
    };
};
