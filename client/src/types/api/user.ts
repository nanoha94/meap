import { IBaseApiIndexResponse } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// ユーザー情報取得
export interface IGetUserResponse {
    name?: string;
    email?: string;
    email_verified_at?: string;
    language?: string;
    avatar_seed: string;
}

// グループユーザー一覧取得
export type IGetGroupUserResponse = IBaseApiIndexResponse<IUser[]>;

//--------------------------------
// データ型
//--------------------------------
export interface IUser {
    id: string;
    name: string;
    language: string;
    avatar: {
        seed: string;
        url: string;
        width: number;
        height: number;
    };
}
