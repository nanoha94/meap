import { ShoppingItemSettingDialogConfig } from '../types/dialogs';

export const SHOPPING_ITEM_EDIT_MODE = {
    CREATE: 'create',
    UPDATE: 'update',
} as const;

export const SHOPPING_ITEM_SETTING_DIALOG_CONFIGS: Record<
    string,
    ShoppingItemSettingDialogConfig
> = {
    [SHOPPING_ITEM_EDIT_MODE.CREATE]: {
        title: '買い物アイテムを追加',
        buttonText: '追加',
    },
    [SHOPPING_ITEM_EDIT_MODE.UPDATE]: {
        title: '買い物アイテムを編集',
        buttonText: '更新',
    },
};
