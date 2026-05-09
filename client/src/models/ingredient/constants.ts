import { TMP_ID_PREFIX } from '@/constants';
import {
    IIngredientItem,
    IPutIngredientCategoryRequest,
} from '@/types';

// ローディング状態キー
export const LOADING_STATE_KEYS = {
    INGREDIENT: 'ingredient',
    INGREDIENT_CATEGORY: 'ingredientCategory',
} as const;

// ------------------------------------------------------------
// デフォルト設定
// ------------------------------------------------------------
// 食材カテゴリ―
export const defaultIngredientCategory: IPutIngredientCategoryRequest = {
    id: `${TMP_ID_PREFIX.INGREDIENT_CATEGORY}${Date.now()}`,
    name: '',
    order: 0,
};

// 食材アイテム
export const defaultIngredientItem: IIngredientItem = {
    id: `${TMP_ID_PREFIX.INGREDIENT_ITEM}${Date.now()}`,
    name: '',
    quantity: null,
    unit: {
        id: '',
        name: '',
        requiresQuantity: true,
        order: 0,
    },
    categoryId: '',
    order: 0,
};
