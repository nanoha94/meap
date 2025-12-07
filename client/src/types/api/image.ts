import { IBaseApiIndexResponse } from './common';

// レスポンス型
// レシピ作成
export type IUploadRecipeResponse = IBaseApiIndexResponse<IImage>;

export interface IImage {
    id?: string;
    src: string;
    width: number;
    height: number;
}

export interface IImageWithFile extends IImage {
    file: File | null;
}
