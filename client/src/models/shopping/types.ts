import { IPostShoppingItemRequest } from "@/types";

// 買い物アイテム編集画面のフォーム型
export type ShoppingItemEditFormData = IPostShoppingItemRequest;

/** 一括作成フォームの行（order / isPinned / isChecked は送信時に付与） */
export type ShoppingItemBulkCreateFormData = {
    categoryId: string;
    items: {
        name: string;
        tags: { id?: string; name: string }[];
    }[];
};

export interface ShoppingListHandle {
    /** 未保存の変更を送り終えるまで待つ（ローディングアニメーションが終わるまで） */
    syncPendingItems: () => Promise<void>;
}
