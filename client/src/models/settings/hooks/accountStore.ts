import {  ILoginUser, IUser } from '@/types/api';
import { create } from 'zustand';

type DialogPayload = {
    invitation: undefined; // データが不要な場合はundefined
    join: undefined;
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface AccountState {
    // state
    dialogs: DialogsState; // ダイアログの状態
    loginUser: ILoginUser; // ログインユーザー
    users: IUser[]; // グループ内ユーザー一覧

    // setter func
    setLoginUser: (loginUser: ILoginUser) => void;
    setUsers: (users: IUser[]) => void;

    // action func
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

const initialDialogsState: DialogsState = {
    invitation: { isOpen: false, payload: undefined },
    join: { isOpen: false, payload: undefined },
};

export const useAccountStore = create<AccountState>(set => ({
    // initial state
    dialogs: initialDialogsState,    
    loginUser: {} as ILoginUser,
    users: [] as IUser[],

    // setter func
    setLoginUser: (loginUser:ILoginUser) => set({ loginUser }),
    setUsers: (users: IUser[]) => set({ users }),

    // action func
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: { isOpen: true, payload },
            },
        })),
    closeDialog: (dialogName: keyof DialogPayload) =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: {
                    ...state.dialogs[dialogName],
                    isOpen: false,
                },
            },
        })),
}));
