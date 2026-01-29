import { AlertDialogConfig, AlertDialogData } from '@/types';

export const ALERT_DIALOG_CONFIG_DEFAULT: AlertDialogConfig = {
    title: '',
    message: [],
    alertMessage: '',
    actionButtonText: '',
};
export const ALERT_DIALOG_STATE_DEFAULT: AlertDialogData = {
    isOpen: false,
    config: ALERT_DIALOG_CONFIG_DEFAULT,
    onCancel: () => {},
    onAction: () => {},
};

/* ダイアログ名 */
export const DIALOG_NAME = {
    // レシピカテゴリー設定
    RECIPE_CATEGORY_SETTING: 'recipeCategorySetting',
    // 食材追加/編集
    INGREDIENT_ADD_EDIT: 'ingredientAddEdit',
    // 食材カテゴリー設定
    INGREDIENT_CATEGORY_SETTING: 'ingredientCategorySetting',
    // 買い物アイテム追加/編集
    SHOPPING_ITEM_ADD_EDIT: 'shoppingItemAddEdit',
} as const;
