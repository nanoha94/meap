export interface IGetShoppingItemsResponse {
    data: {
        category: IShoppingCategory;
        items: IShoppingItem[];
    }[];
}

export interface IGetShoppingCategoriesResponse {
    data: IShoppingCategory[];
    total: number;
}

export interface IPutShoppingItemRequest {
    data: {
        id: string;
        name: string;
        isPinned: boolean;
        isChecked: boolean;
        categoryId: string;
        tags: { id?: string; name: string }[];
        order: number;
    }[];
}

export interface IPostShoppingItemRequest {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

export interface IPostShoppingCategoryRequest {
    name: string;
    order: number;
}

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
