import { IImage } from './image';
import { IIngredientItem } from './ingredient';
import {
    IBaseApiIndexResponse,
    IBaseApiResponse,
    IBaseApiDeleteResponse,
} from './common';

// レスポンス型
// レシピ一覧取得
export type IGetRecipeIndexResponse = IBaseApiIndexResponse<IRecipe[]>;

// レシピ詳細取得
export type IGetRecipeShowResponse = IBaseApiResponse<IRecipe>;

// レシピ作成
export type IPostRecipeResponse = IBaseApiResponse<IRecipe>;

// レシピ更新
export type IPutRecipeResponse = IBaseApiResponse<IRecipe>;

// レシピ削除
export type IDeleteRecipeResponse = IBaseApiDeleteResponse;

// リクエスト型
// レシピ作成/更新
export interface IPostPutRecipeRequest {
    id?: string;
    name: string;
    url?: string;
    memo?: string;
    servingCount?: number | null;
    thumbnailId?: string;
    categoryIds: string[];
    ingredients?: {
        id?: string;
        name: string;
        quantity: number | null;
        unitId: string;
        categoryId: string;
        order?: number;
    }[];
    steps?: {
        id?: string;
        instruction: string;
        imageId?: string;
        order: number;
    }[];
}

// レシピカテゴリー作成
export interface IPostRecipeCategoryRequest {
    name: string;
    order: number;
}

// レシピカテゴリー
export interface IRecipeCategory {
    id: string;
    name?: string; // nameは省略可（idだけで十分な場合もある）
    order: number;
}

// レシピ手順
export interface IRecipeStep {
    id?: string;
    instruction: string;
    image: IImage | null;
    order: number;
}

// レシピ
export interface IRecipe {
    id: string;
    userId: string;
    name: string;
    url: string;
    memo: string;
    servingCount: number | null;
    thumbnail: IImage | null;
    categories: IRecipeCategory[];
    ingredients: IIngredientItem[];
    steps: IRecipeStep[];
}
