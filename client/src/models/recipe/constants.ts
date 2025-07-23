import { IPostRecipeRequest } from '@/types/api/recipe';
import { RecipeSettingDialogConfigs } from './types';

export enum TMP_ID_PREFIX {
    RECIPE_CATEGORY = 'meap-recipe-category-',
    RECIPE_INGREDIENT = 'meap-recipe-ingredient-',
    RECIPE_SEASONING = 'meap-recipe-seasoning-',
}

export const RECIPE_SETTING_DIALOG_NAME = {
    CATEGORY: 'categorySetting',
    INGREDIENT: 'ingredientSetting',
    SEASONING: 'seasoningSetting',
} as const;

export const RECIPE_SETTING_DIALOG_EDIT_MODE = {
    CREATE: 'create',
    UPDATE: 'update',
} as const;

export const RECIPE_SETTING_DIALOG_CONFIGS: RecipeSettingDialogConfigs = {
    [RECIPE_SETTING_DIALOG_NAME.CATEGORY]: {
        title: 'カテゴリを設定',
        actionButtonText: '設定',
    },
    [RECIPE_SETTING_DIALOG_NAME.INGREDIENT]: {
        [RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE]: {
            title: '食材を追加',
            actionButtonText: '追加',
        },
        [RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE]: {
            title: '食材を編集',
            actionButtonText: '更新',
        },
    },
    [RECIPE_SETTING_DIALOG_NAME.SEASONING]: {
        [RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE]: {
            title: '調味料を追加',
            actionButtonText: '追加',
        },
        [RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE]: {
            title: '調味料を編集',
            actionButtonText: '更新',
        },
    },
};

export const defaultRecipeCategory = {
    id: `${TMP_ID_PREFIX.RECIPE_CATEGORY}-0`,
    name: '',
};
export const defaultIngredient = {
    id: `${TMP_ID_PREFIX.RECIPE_INGREDIENT}-0`,
    name: '',
    quantity: undefined,
    unitId: '',
};
export const defaultSeasoning = {
    id: `${TMP_ID_PREFIX.RECIPE_SEASONING}-0`,
    name: '',
    quantity: undefined,
    unitId: '',
};
export const defaultPostData: IPostRecipeRequest = {
    name: '',
    url: '',
    recipe: '',
    memo: '',
    categories: [defaultRecipeCategory],
    seasonings: [defaultSeasoning],
    ingredients: [defaultIngredient],
    thumbnailImage: new File([], ''),
};
