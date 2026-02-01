import { IBaseApiIndexResponse } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// 買い物アイテム一覧取得
export type IGetShoppingItemIndexResponse = IBaseApiIndexResponse<
    IShoppingItem[]
>;

// 買い物カテゴリー一覧取得
export type IGetShoppingCategoryIndexResponse = IBaseApiIndexResponse<
    IShoppingCategory[]
>;

//--------------------------------
// リクエストデータ型
//--------------------------------
// 買い物アイテム更新
export interface IPutShoppingItemRequest {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    tags: { id?: string; name: string }[];
    order: number;
}

// 買い物アイテム作成
export interface IPostShoppingItemRequest {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

// 買い物カテゴリー作成
export interface IPostShoppingCategoryRequest {
    name: string;
    order: number;
}

// 買い物カテゴリー更新
export interface IPutShoppingCategoryRequest {
    id: string;
    name: string;
    order: number;
}

//--------------------------------
// データ型
//--------------------------------
export interface IShoppingItem {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    tags: { id: string; name: string }[];
    order: number;
}

export interface IShoppingCategory {
    id: string;
    name: string;
    isDefault: boolean;
    order: number;
}

export interface IShoppingTag {
    id: string;
    name: string;
}
