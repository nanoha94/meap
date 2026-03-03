import { IBaseApiResponseWithData, IBaseApiIndexResponse } from './common';
import { IImage } from './image';

//--------------------------------
// レスポンス型
//--------------------------------
// ユーザー情報取得
export type IGetUserResponse = IBaseApiResponseWithData<ILoginUser>;

// グループユーザー一覧取得
export type IGetGroupUserResponse = IBaseApiIndexResponse<IUser[]>;

//--------------------------------
// リクエスト型
//--------------------------------
// ユーザー情報更新
export interface IPutUserRequest {
    name: string;
    avatar_image_id?: string;
}


//--------------------------------
// データ型
//--------------------------------
export interface ILoginUser {
    id: string;
    name?: string;
    email?: string;
    email_verified_at?: string;
    language?: string;
    avatar: {
        seed: string;
        image?: IImage;
    };
}

export interface IUser {
    id: string;
    name: string;
    language: string;
    avatar: {
        seed: string;
        image?: IImage;
    };
}

