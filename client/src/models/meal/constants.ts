import { AlertDialogConfig } from "@/types";

// アラートダイアログの設定
export const MEAL_ALERT_DIALOG_CONFIGS = {
    // 献立を削除（１食分削除）
    deleteItem: (name: string): AlertDialogConfig => ({
        title: '削除',
        message: [`${name}を削除しますか？`],
        alertMessage: '',
        actionButtonText: '削除',
    }),
};