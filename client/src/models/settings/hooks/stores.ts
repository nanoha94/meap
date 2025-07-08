import { IGetUserResponse, IGetGroupUserResponse } from '@/types/api';
import { create } from 'zustand';
import { JoinCheckDialogConfig } from '../types/dialogs';

type DialogPayload = {
    invitation: undefined; // データが不要な場合はundefined
    join: undefined;
    deleteCheck: JoinCheckDialogConfig | undefined;
};

// TODO: payloadが不要なら削除（とりあえず取っておく）
type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface AccountState {
    // ダイアログの状態
    dialogs: DialogsState;

    // ログインユーザー
    loginUser: IGetUserResponse;

    // 同じグループのユーザー
    users: IGetGroupUserResponse['data'];

    // ダイアログのアクション
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;

    // ログインユーザーのアクション
    setLoginUser: (loginUser: IGetUserResponse) => void;

    // 同じグループのユーザーのアクション
    setUsers: (users: IGetGroupUserResponse['data']) => void;
}

const initialDialogsState: DialogsState = {
    invitation: { isOpen: false, payload: undefined },
    join: { isOpen: false, payload: undefined },
    deleteCheck: { isOpen: false, payload: undefined },
};

export const useAccountStore = create<AccountState>(set => ({
    // 初期状態
    dialogs: initialDialogsState,
    loginUser: {} as IGetUserResponse,
    users: [] as IGetGroupUserResponse['data'],

    // ダイアログのアクション
    openDialog: (dialogName, payload) =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: { isOpen: true, payload },
            },
        })),
    closeDialog: dialogName =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: {
                    ...state.dialogs[dialogName],
                    isOpen: false,
                },
            },
        })),

    // ログインユーザーのアクション
    setLoginUser: loginUser => set({ loginUser }),

    // 同じグループのユーザーのアクション
    setUsers: users => set({ users }),
}));
