import { TMP_ID_PREFIX } from '@/constants';
import { IIngredientItem, IRecipeStep } from '@/types/api';
import { IPostPutRecipeRequest } from '@/types/api';

/**
 * 食材をフォーマット
 * @param items 食材リスト
 * @returns フォーマットされた食材
 */
export const formatIngredientItems = (
    items: IIngredientItem[],
): IPostPutRecipeRequest['ingredients'] => {
    return items
        .filter(v => v.name && v.name.length > 0)
        .map((v, idx) => {
            const isNew = v.id?.startsWith(TMP_ID_PREFIX.INGREDIENT_ITEM);
            return {
                ...(isNew ? {} : { id: v.id }),
                name: v.name,
                quantity: v.quantity,
                unitId: v.unit?.id ?? '',
                categoryId: v.categoryId,
                order: idx,
            };
        });
};

/**
 * 手順をフォーマット
 * @param items 手順リスト
 * @returns フォーマットされた手順
 */
export const formatStepItems = (
    items: IRecipeStep[],
): IPostPutRecipeRequest['steps'] => {
    return items
        .filter(v => v.instruction && v.instruction.length > 0)
        .map((v, idx) => {
            const isNew = v.id?.startsWith(TMP_ID_PREFIX.RECIPE_STEP);
            return {
                ...(isNew ? {} : { id: v.id }),
                instruction: v.instruction,
                imageId: v.image?.id,
                order: idx,
            };
        });
};
