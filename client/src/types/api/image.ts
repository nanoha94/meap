import { IBaseApiIndexResponse } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// 画像アップロード
export type IUploadImageResponse = IBaseApiIndexResponse<IImage>;

//--------------------------------
// データ型
//--------------------------------
export interface IImage {
    id?: string;
    src: string;
    width: number;
    height: number;
}
export interface IImageWithFile extends IImage {
    file: File | null;
}
