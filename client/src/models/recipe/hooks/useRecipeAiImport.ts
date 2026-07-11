'use client';

import React from 'react';
import { UseFormReturn } from 'react-hook-form';

import { TMP_ID_PREFIX } from '@/constants';
import { defaultIngredientItem, useIngredientStore } from '@/models/ingredient';
import {
    IIngredientCategory,
    IIngredientItem,
    IIngredientUnit,
    IParsedRecipe,
    IParsedRecipeIngredient,
} from '@/types';
import {
    createDefaultData,
    formatQuantityDisplay,
    getItemsInCategory,
} from '@/utils';
import { DEFAULT_RECIPE_STEP } from '../constants';
import { RecipeAiImportFormData, RecipeEditFormData, RecipeStepEditFormData } from '../types';

/**
 * マスター名を正規化する
 * @param name マスター名
 * @returns 正規化されたマスター名
 */
const normalizeMasterName = (name: string): string => name.trim().toLowerCase();

/**
 * マスターリストからマスターを取得する
 * @param items マスターリスト（食材カテゴリーリストや食材単位リストなど）
 * @param name マスター名
 * @returns マスター（食材カテゴリーや食材単位など）
 */
const findMasterByName = <T extends { name: string }>(
    items: T[],
    name: string,
): T | undefined => {
    const normalized = normalizeMasterName(name);
    if (!normalized) {
        return undefined;
    }

    const exact = items.find(
        item => normalizeMasterName(item.name) === normalized,
    );
    if (exact) {
        return exact;
    }

    return items.find(item => {
        const itemName = normalizeMasterName(item.name);

        // 部分一致でマスターを取得
        return itemName.includes(normalized) || normalized.includes(itemName);
    });
};

/**
 * 食材カテゴリーリストからデフォルトカテゴリーを取得する
 * @param categories 食材カテゴリーリスト
 * @returns デフォルトカテゴリー
 */
const getFallbackCategory = (
    categories: IIngredientCategory[],
): IIngredientCategory | undefined => {
    return categories.find(category => category.isDefault) ?? categories[0];
};

/**
 * 食材単位リストからデフォルト単位を取得する
 * @param units 食材単位リスト
 * @returns デフォルト単位
 */
const getFallbackUnit = (
    units: IIngredientUnit[],
): IIngredientUnit | undefined => {
    return (
        units.find(unit => unit.name === '適量') ??
        units.find(unit => !unit.requiresQuantity) ??
        units[0]
    );
};

/**
 * AI 解析結果をレシピ食材編集フォーム用データへ変換する
 * @param parsedIngredients AI 解析結果の食材リスト
 * @param units 食材単位リスト
 * @param categories 食材カテゴリーリスト
 * @returns レシピ食材編集フォーム用データ
 */
const buildIngredientsFromParsed = (
    parsedIngredients: IParsedRecipeIngredient[],
    units: IIngredientUnit[],
    categories: IIngredientCategory[],
): IIngredientItem[] => {
    const prefix = TMP_ID_PREFIX.INGREDIENT_ITEM;

    // デフォルトカテゴリーとデフォルト単位を取得（該当するものがない場合はデフォルトを使用）
    const fallbackCategory = getFallbackCategory(categories);
    const fallbackUnit = getFallbackUnit(units);

    const aiItems: IIngredientItem[] = parsedIngredients
        .filter(ingredient => ingredient.name.trim().length > 0)
        .map((ingredient, index) => {
            const matchedUnit =
                findMasterByName(units, ingredient.unitName) ?? fallbackUnit;
            const matchedCategory =
                findMasterByName(categories, ingredient.categoryName) ??
                fallbackCategory;

            const requiresQuantity = matchedUnit?.requiresQuantity;
            let quantity: number | null = null;
            let quantityDisplay: string | null = null;

            if (requiresQuantity) {
                quantity = ingredient.quantity;
                quantityDisplay =
                    ingredient.quantityDisplay ??
                    (quantity != null
                        ? formatQuantityDisplay(quantity, null)
                        : null);
            }

            return {
                ...createDefaultData(defaultIngredientItem, prefix),
                name: ingredient.name,
                quantity,
                quantityDisplay,
                unit: matchedUnit ?? null,
                categoryId: matchedCategory?.id ?? '',
                order: index,
            };
        });

    if (categories.length === 0) {
        return aiItems;
    }

    return categories
        .map(category => {
            const itemsInCategory = getItemsInCategory(aiItems, category.id);
            if (itemsInCategory.length > 0) {
                return itemsInCategory;
            }

            return [
                {
                    ...createDefaultData(defaultIngredientItem, prefix),
                    categoryId: category.id,
                },
            ];
        })
        .flat();
};

/**
 * AI 解析結果をレシピ手順編集フォーム用データへ変換する
 * @param parsedSteps AI 解析結果の手順リスト
 * @returns レシピ手順編集フォーム用データ
 */
const buildStepsFromParsed = (
    parsedSteps: IParsedRecipe['steps'],
): RecipeStepEditFormData[] => {
    const prefix = TMP_ID_PREFIX.RECIPE_STEP;
    const steps = parsedSteps
        .filter(step => step.instruction.trim().length > 0)
        .map((step, index) => ({
            ...createDefaultData(DEFAULT_RECIPE_STEP, prefix),
            instruction: step.instruction,
            order: index,
        }));

    if (steps.length === 0) {
        return [createDefaultData(DEFAULT_RECIPE_STEP, prefix)];
    }

    return steps;
};

/**
 * 画像から読み込んだ AI 解析結果をレシピ編集フォームへ反映するフック
 */
export const useRecipeAiImport = () => {
    const units = useIngredientStore(state => state.units);
    const categories = useIngredientStore(state => state.categories);

    /**
     * AI 解析結果をレシピ編集フォーム用データへ変換する
     */
    const convertToFormData = React.useCallback(
        (parsed: IParsedRecipe): RecipeAiImportFormData => {
            return {
                name: parsed.name,
                servingCount: parsed.servingCount,
                ingredients: buildIngredientsFromParsed(
                    parsed.ingredients,
                    units,
                    categories,
                ),
                steps: buildStepsFromParsed(parsed.steps),
            };
        },
        [units, categories],
    );

    /**
     * 変換済みデータをレシピ編集フォームへ投入する
     */
    const applyParsedRecipeToForm = React.useCallback(
        (
            formData: RecipeAiImportFormData,
            methods: Pick<
                UseFormReturn<RecipeEditFormData>,
                'setValue'
            >,
        ): void => {
            const { setValue } = methods;

            setValue('name', formData.name);
            setValue('servingCount', formData.servingCount);
            setValue('ingredients', formData.ingredients);
            setValue('steps', formData.steps);
        },
        [],
    );

    return {
        convertToFormData,
        applyParsedRecipeToForm,
    };
};
