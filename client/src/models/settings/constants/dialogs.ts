import { JoinCheckDialogConfig } from '../types/dialogs';
import { JoinErrorType } from './error';

export const JOIN_CHECK_DIALOG_CONFIGS: Record<
    (typeof JoinErrorType)[keyof typeof JoinErrorType],
    JoinCheckDialogConfig
> = {
    [JoinErrorType.ALREADY_IN_GROUP]: {
        message: '現在のグループを退出して\n新しいグループに参加しますか？',
        buttonText: '退出して参加',
    },
    [JoinErrorType.HAS_EXISTING_DATA]: {
        message:
            'すでに登録済みのデータがあります。\n削除してグループに参加しますか？',
        alertMessage: '※削除したデータは復元できません',
        buttonText: '削除して参加',
    },
};
