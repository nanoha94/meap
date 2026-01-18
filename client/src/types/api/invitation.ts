import { IBaseApiResponse } from './common';
import { IUser } from './user';

//--------------------------------
// レスポンス型
//--------------------------------
// 招待リンク発行
export type IGetInvitationDetailResponse = IBaseApiResponse<IInvitation>;

// 招待トークン詳細取得
export type IPostInvitaionResponse =  IBaseApiResponse<Omit<IInvitation, 'inviter'>>;

// グループへの参加
export type IPostInvitationJoinResponse = IBaseApiResponse<null>;

//--------------------------------
// データ型
//--------------------------------
export interface IInvitation {
    token: string;
    expires_at: string;
    inviter: IUser;
}
