import { AlertDialogConfig } from '@/types';

export const DEBOUNCE_DELAY = 5000;


// アラートダイアログの設定
export const SHOPPING_ALERT_DIALOG_CONFIGS = {
    // カテゴリーからアイテムを削除
    deleteItemsFromCategory: (categoryName: string, items: string[]): AlertDialogConfig => ({
        title: `${categoryName}から買い物アイテムを削除する`,
        message: [
            'チェック済みの買い物アイテムをすべて削除しますか？',
            items.join(' / '),
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
