import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';

export type IngredientSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type IngredientSettingDialogConfigs = {
    [DIALOG_NAME.INGREDIENT_ADD_EDIT]: {
        [DIALOG_EDIT_MODE.CREATE]: IngredientSettingDialogConfig;
        [DIALOG_EDIT_MODE.UPDATE]: IngredientSettingDialogConfig;
    };
    [DIALOG_NAME.INGREDIENT_CATEGORY_SETTING]: IngredientSettingDialogConfig;
};
