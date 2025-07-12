import { ShoppingItemSettingDialogConfig } from './types';

export enum TMP_ID_PREFIX {
    SHOPPING_CATEGORY = 'meap-shopping-category-',
}

export const DEBOUNCE_DELAY = 5000;
export const DRAG_ACTIVATION_DISTANCE = 5;

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
