export const JoinErrorType = {
    ALREADY_IN_GROUP: 'already_in_group',
    HAS_EXISTING_DATA: 'has_existing_data',
} as const;

export const JoinCheckDialogConfig = {
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
} as const;
