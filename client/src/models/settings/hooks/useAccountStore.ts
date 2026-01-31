import { create } from 'zustand';

import { ILoginUser, IUser } from '@/types';

interface AccountState {
    // state
    loginUser: ILoginUser; // ログインユーザー
    users: IUser[]; // グループ内ユーザー一覧

    // setter func
    setLoginUser: (loginUser: ILoginUser) => void;
    setUsers: (users: IUser[]) => void;
}

export const useAccountStore = create<AccountState>(set => ({
    // initial state
    loginUser: {} as ILoginUser,
    users: [] as IUser[],

    // setter func
    setLoginUser: (loginUser:ILoginUser) => set({ loginUser }),
    setUsers: (users: IUser[]) => set({ users }),
}));
