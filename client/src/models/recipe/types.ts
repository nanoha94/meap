import { DIALOG_NAME } from '@/constants';

export type RecipeSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: RecipeSettingDialogConfig;
};

export type Thumbnail = {
    file: File | null;
    src: string;
    width: number;
    height: number;
};
