import { TMP_ID_PREFIX } from '@/constants';
import { IIngredientCategory, IIngredientItem } from '@/types';

// ローディング状態キー
export const LOADING_STATE_KEYS = {
    INGREDIENT: 'ingredient',
    INGREDIENT_CATEGORY: 'ingredientCategory',
} as const;

// ------------------------------------------------------------
// デフォルト設定
// ------------------------------------------------------------
// 食材カテゴリ―（新規追加用。レシピ新規作成時の初期カテゴリーは isDefault: true）
export const defaultIngredientCategory: IIngredientCategory = {
    id: `${TMP_ID_PREFIX.INGREDIENT_CATEGORY}${Date.now()}`,
    name: '',
    isDefault: false,
    order: 0,
};

/** レシピ新規作成時のデフォルト食材カテゴリー（「食材」） */
export const createDefaultRecipeIngredientCategory = (): IIngredientCategory => ({
    id: `${TMP_ID_PREFIX.INGREDIENT_CATEGORY}${Date.now()}`,
    name: '食材',
    isDefault: true,
    order: 0,
});

// 食材アイテム
export const defaultIngredientItem: IIngredientItem = {
    id: `${TMP_ID_PREFIX.INGREDIENT_ITEM}${Date.now()}`,
    name: '',
    quantity: null,
    quantityDisplay: null,
    unit: {
        id: '',
        name: '',
        requiresQuantity: true,
        order: 0,
    },
    categoryId: '',
    order: 0,
};
