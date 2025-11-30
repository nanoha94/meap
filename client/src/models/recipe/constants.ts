import { IRecipe, IRecipeCategory } from '@/types/api';
import { RecipeSettingDialogConfigs, RecipeStepEditFormData } from './types';
import { DIALOG_NAME, TMP_ID_PREFIX } from '@/constants';

/* ダイアログ設定 */
export const RECIPE_SETTING_DIALOG_CONFIGS: RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: {
        title: '料理カテゴリーを設定',
        actionButtonText: '設定',
    },
};

/** デフォルト設定 */
export const defaultRecipeCategory: IRecipeCategory = {
    id: `${TMP_ID_PREFIX.RECIPE_CATEGORY}${Date.now()}`,
    name: '',
    order: 0,
};

export const defaultRecipeStep: RecipeStepEditFormData = {
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

export const defaultPostData: IRecipe = {
    id: '',
    name: '',
    url: '',
    memo: '',
    thumbnail: null,
    categories: [],
    ingredients: [],
    steps: [],
};
