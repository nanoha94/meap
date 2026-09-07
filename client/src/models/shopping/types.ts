import { IIngredientItem, IPostShoppingItemRequest } from "@/types";

// 買い物アイテム編集画面のフォーム型
export type ShoppingItemEditFormData = IPostShoppingItemRequest;

/** 一括作成フォームの行（order / isPinned / isChecked は送信時に付与） */
export type ShoppingItemBulkCreateFormItem = {
    /** 表示・チェック用（formatIngredient の結果） */
    name: string;
    ingredient: Pick<IIngredientItem, "name" | "quantity" | "quantityDisplay" | "unit">;
    mealId: string;
    tags: { id?: string; name: string }[];
};

export type ShoppingItemBulkCreateFormData = {
    categoryId: string;
    items: ShoppingItemBulkCreateFormItem[];
};

export interface ShoppingListHandle {
    /** 未保存の変更を送り終えるまで待つ（ローディングアニメーションが終わるまで） */
    syncPendingItems: () => Promise<void>;
}
