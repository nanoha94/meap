import { ShoppingItemSettingDialogConfig } from './types';
import { AlertDialogConfig } from '@/types/dialog';

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

// ダイアログ設定を生成する関数群
export const SHOPPING_ALERT_DIALOG_CONFIGS = {
    // カテゴリーからアイテムを削除
    deleteItemsFromCategory: (categoryName: string): AlertDialogConfig => ({
        title: `${categoryName}から買い物アイテムを削除する`,
        message: [
            `${categoryName}に登録されているすべての買い物アイテムを削除しますか？`,
            '※固定化アイテムは削除されません',
        ],
        alertMessage: '',
        actionButtonText: '削除',
    }),

    // 単一アイテムを削除
    deleteItem: (itemName: string): AlertDialogConfig => ({
        title: '買い物アイテムを削除する',
        message: [`${itemName}を削除しますか？`],
        alertMessage: '',
        actionButtonText: '削除',
    }),
};
