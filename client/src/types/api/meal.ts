import { IBaseApiIndexResponse, IBaseApiResponse, IBaseApiResponseWithData } from "./common";
import { IImage } from "./image";

//--------------------------------
// レスポンス型
//--------------------------------

// 献立プラン一覧取得
export type IGetMealPlanIndexResponse = IBaseApiIndexResponse<IMealPlan[]>;

// 献立プラン詳細取得
export type IGetMealPlanShowResponse = IBaseApiResponseWithData<IMealPlan>;

// 献立プラン作成
export type IPostMealPlanResponse = IBaseApiResponse;

// 献立プラン更新
export type IPutMealPlanResponse = IBaseApiResponse;

// 献立プラン削除
export type IDeleteMealPlanResponse = IBaseApiResponse;

//--------------------------------
// リクエスト型
//--------------------------------
// 献立プラン作成/更新
export interface IPostPutMealPlanRequest {
    id?: string;
    date?: string;
    meals: {
        id?: string;
        categoryId: string;
        recipes: { id: string, order: number }[],
        order: number
    }[];
}

//--------------------------------
// データ型
//--------------------------------
export interface IMealCategory {
    id: string;
    name: string;
    colorCodeHex: string;
    order: number;
}

export interface IMealPlanItem {
    id: string;                        // 献立1品のID（pivot）
    categoryId: string;                // 献立カテゴリID
    order: number;                     // 表示順
    recipeId: string;                  // レシピID
    recipeName: string;                // レシピ/料理名
    recipeThumbnail: IImage | null;    // レシピ/料理サムネイル画像
    recipeOrder: number;               // レシピ/料理並び順
}

export interface IMealPlan {
    id: string;
    date: string;
    meals: IMealPlanItem[];
}

