import { JoinCheckDialogConfig } from './types';

// Error constants
export const JOIN_ERROR_TYPE = {
    ALREADY_IN_GROUP: 'already_in_group',
    HAS_EXISTING_DATA: 'has_existing_data',
} as const;

// Dialog constants
export const JOIN_CHECK_DIALOG_CONFIGS: Record<
    (typeof JOIN_ERROR_TYPE)[keyof typeof JOIN_ERROR_TYPE],
    JoinCheckDialogConfig
> = {
    [JOIN_ERROR_TYPE.ALREADY_IN_GROUP]: {
        message: '現在のグループを退出して\n新しいグループに参加しますか？',
        buttonText: '退出して参加',
    },
    [JOIN_ERROR_TYPE.HAS_EXISTING_DATA]: {
        message:
            'すでに登録済みのデータがあります。\n削除してグループに参加しますか？',
        alertMessage: '※削除したデータは復元できません',
        buttonText: '削除して参加',
    },
};
