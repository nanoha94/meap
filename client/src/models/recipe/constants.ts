import { TMP_ID_PREFIX } from '@/constants';
import { AlertDialogConfig,  IRecipeCategory } from '@/types';
import { RecipeEditFormData,  RecipeStepEditFormData } from './types';

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

export const DEFAULT_RECIPE_EDIT_FORM_DATA: RecipeEditFormData = {
    id: '',
    name: '',
    url: '',
    memo: '',
    servingCount: null,
    thumbnail: null,
    categories: [],
    ingredients: [],
    steps: [],
};

