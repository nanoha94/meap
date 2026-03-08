import { AlertDialogConfig } from "@/types";

// アラートダイアログの設定
export const ALERT_DIALOG_CONFIGS = {
    // 未保存の編集を破棄
    unsavedChanges: (): AlertDialogConfig => ({
        title: '編集内容の破棄',
        message: ['保存されていない編集内容があります。', 'このページを離れると編集内容は失われます。'],
        alertMessage: '',
        actionButtonText: '破棄する',
    }),
};