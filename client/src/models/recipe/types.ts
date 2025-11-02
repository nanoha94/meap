import { DIALOG_NAME } from '@/constants';

export type RecipeSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: RecipeSettingDialogConfig;
};
