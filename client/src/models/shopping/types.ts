import { IPostShoppingItemRequest } from "@/types";

// 買い物アイテム編集画面のフォーム型
export type ShoppingItemEditFormData = IPostShoppingItemRequest;

export type ShoppingItemBulkCreateFormData = {
    categoryId: string;
    items: Omit<IPostShoppingItemRequest, 'categoryId'>[];
};

export interface ShoppingListHandle {
    /** 未保存の変更を送り終えるまで待つ（ローディングアニメーションが終わるまで） */
    syncPendingItems: () => Promise<void>;
}
