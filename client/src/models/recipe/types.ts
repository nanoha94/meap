import {
    RECIPE_SETTING_DIALOG_EDIT_MODE,
    RECIPE_SETTING_DIALOG_NAME,
} from './constants';

export type RecipeSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type RecipeSettingDialogConfigs = {
    [RECIPE_SETTING_DIALOG_NAME.CATEGORY]: RecipeSettingDialogConfig;
    [RECIPE_SETTING_DIALOG_NAME.INGREDIENT]: {
        [RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE]: RecipeSettingDialogConfig;
        [RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE]: RecipeSettingDialogConfig;
    };
    [RECIPE_SETTING_DIALOG_NAME.SEASONING]: {
        [RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE]: RecipeSettingDialogConfig;
        [RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE]: RecipeSettingDialogConfig;
    };
};
