import { IImage } from './image';
import { IIngredientItem } from './ingredient';
import {
    IBaseApiIndexResponse,
    IBaseApiResponse,
    IBaseApiResponseWithData,
} from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// レシピ一覧取得
export type IGetRecipeIndexResponse = IBaseApiIndexResponse<IRecipe[]>;

// レシピ詳細取得
export type IGetRecipeShowResponse = IBaseApiResponseWithData<IRecipe>;

// レシピ作成
export type IPostRecipeResponse = IBaseApiResponse;

// レシピ更新
export type IPutRecipeResponse = IBaseApiResponse;

// レシピ削除
export type IDeleteRecipeResponse = IBaseApiResponse;

// レシピカテゴリ一覧取得
export type IGetRecipeCategoryIndexResponse = IBaseApiIndexResponse<IRecipeCategory[]>;

//--------------------------------
// リクエスト型
//--------------------------------
// レシピ一覧取得
export interface IGetRecipeIndexRequest {
    limit?: number;
    offset?: number;
    sort?: string;
    order?: string;
    recipe_name?: string;
    ingredient_name?: string;
    category_ids?: string[];
    last_planned_date_from?: string;
    last_planned_date_to?: string;
}

// レシピ作成/更新
export interface IPostPutRecipeRequest {
    id?: string;
    name: string;
    url?: string;
    memo?: string;
    servingCount?: number | null;
    thumbnailId?: string;
    categoryIds: string[];
    ownerUserId: string;
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

// レシピカテゴリー更新
export interface IPutRecipeCategoryRequest {
    id: string;
    name: string;
    order: number;
}

//--------------------------------
// データ型
//--------------------------------
export interface IRecipeCategory {
    id: string;
    name: string;
    order: number;
}

export interface IRecipeStep {
    id?: string;
    instruction: string;
    image: IImage | null;
    order: number;
}

export interface IRecipe {
    id: string;
    ownerUserId: string; // 編集責任者のユーザーID
    name: string;
    url: string;
    memo: string;
    servingCount: number | null;
    thumbnail: IImage | null;
    categories: IRecipeCategory[];
    ingredients: IIngredientItem[];
    steps: IRecipeStep[];
    lastPlannedDate: string | null;
    cookingTime: number | null;
}

// 一覧用
export type IRecipeListItem = Pick<IRecipe, 'id' | 'name' | 'categories' | 'thumbnail' | 'lastPlannedDate'>;
