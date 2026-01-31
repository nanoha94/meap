import { DIALOG_NAME, TMP_ID_PREFIX } from '@/constants';
import { AlertDialogConfig, IRecipe, IRecipeCategory } from '@/types';
import { RecipeSettingDialogConfigs, RecipeStepEditFormData } from './types';

/* ダイアログ設定 */
export const RECIPE_SETTING_DIALOG_CONFIGS: RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: {
        title: '料理カテゴリーを設定',
        actionButtonText: '設定',
    },
};

// アラートダイアログの設定
export const RECIPE_ALERT_DIALOG_CONFIGS = {
    // レシピを削除
    deleteItem: (name: string): AlertDialogConfig => ({
        title: '削除',
        message: [`${name}を削除しますか？`],
                alertMessage: '',
                actionButtonText: '削除',
    }),   
};

/** デフォルト設定 */
export const DEFAULT_RECIPE_CATEGORY: IRecipeCategory = {
    id: `${TMP_ID_PREFIX.RECIPE_CATEGORY}${Date.now()}`,
    name: '',
    order: 0,
};

export const DEFAULT_RECIPE_STEP: RecipeStepEditFormData = {
    id: `${TMP_ID_PREFIX.RECIPE_STEP}${Date.now()}`,
    instruction: '',
    image: {
        file: null,
        src: '',
        width: 0,
        height: 0,
    },
    order: 0,
};

export const DEFAULT_POST_DATA: IRecipe = {
    id: '',
    ownerUserId: '',
    name: '',
    url: '',
    memo: '',
    servingCount: null,
    thumbnail: null,
    categories: [],
    ingredients: [],
    steps: [],
};

