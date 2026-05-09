/* 編集モード */
export const EDIT_MODE = {
    CREATE: 'create',
    UPDATE: 'update',
} as const;
export type EditMode = (typeof EDIT_MODE)[keyof typeof EDIT_MODE];
