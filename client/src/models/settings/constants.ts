import { AlertDialogConfig } from '@/types/dialog';

// joinリクエストをしたときのエラー種別
export const JOIN_ERROR_TYPE = {
    ALREADY_IN_GROUP: 'already_in_group',
    HAS_EXISTING_DATA: 'has_existing_data',
} as const;

// joinリクエストのエラー内容に応じて表示するダイアログ設定
export const DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS: Record<
    (typeof JOIN_ERROR_TYPE)[keyof typeof JOIN_ERROR_TYPE],
    AlertDialogConfig
> = {
    [JOIN_ERROR_TYPE.ALREADY_IN_GROUP]: {
        title: 'データ削除',
        message: ['現在のグループを退出して\n新しいグループに参加しますか？'],
        alertMessage: '',
        actionButtonText: '退出して参加',
    },
    [JOIN_ERROR_TYPE.HAS_EXISTING_DATA]: {
        title: 'データ削除',
        message: [
            'すでに登録済みのデータがあります。\n削除してグループに参加しますか？',
        ],
        alertMessage: '※削除したデータは復元できません',
        actionButtonText: '削除して参加',
    },
};
