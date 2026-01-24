import { EDIT_MODE } from '@/constants';
import { ShoppingItemSettingDialogConfig } from './types';
import { AlertDialogConfig } from '@/types';

export const DEBOUNCE_DELAY = 5000;

export const SHOPPING_ITEM_SETTING_DIALOG_CONFIGS: Record<
    string,
    ShoppingItemSettingDialogConfig
> = {
    [EDIT_MODE.CREATE]: {
        title: '買い物アイテムを追加',
        buttonText: '追加',
    },
    [EDIT_MODE.UPDATE]: {
        title: '買い物アイテムを編集',
        buttonText: '保存',
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
