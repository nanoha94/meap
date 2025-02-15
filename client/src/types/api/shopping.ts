export interface IGetShoppingItem {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    order: number;
}

export interface IPostShoppingItem {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    order: number;
}

export interface IGetShoppingCategory {
    id: string;
    name: string;
    isDefault: boolean;
    order: number;
}

export interface IPostShoppingCategory {
    id: string;
    name: string;
    order?: number;
}
