export interface IGetShoppingItemsResponse {
    data: {
        category: IShoppingCategory;
        items: IShoppingItem[];
    }[];
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

export interface IPostShoppingItem {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

export interface IShoppingCategory {
    id: string;
    name: string;
    isDefault: boolean;
    order: number;
}

export interface IPostShoppingCategory {
    name: string;
    order: number;
}
