import { IRecipe2, IRecipeCategory } from '@/types/api/recipe';
import { RecipeSettingDialogConfigs } from './types';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { DIALOG_NAME } from '@/constants';

/* ダイアログ設定 */
export const RECIPE_SETTING_DIALOG_CONFIGS: RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: {
        title: '料理カテゴリーを設定',
        actionButtonText: '設定',
    },
};

/** デフォルト設定 */
export const defaultRecipeCategory: IRecipeCategory = {
    id: `${TMP_ID_PREFIX.RECIPE_CATEGORY}-0`,
    name: '',
    order: 0,
};

export const defaultPostData: IRecipe2 = {
    id: '',
    name: '',
    url: '',
    memo: '',
    thumbnail: null,
    categories: [],
    ingredients: [],
    steps: [],
};
