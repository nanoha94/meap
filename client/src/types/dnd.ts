import { ICategoryWithItems } from './api/common';

/**
 * ドラッグオーバー時のターゲット情報返却データ型
 * @property isOverItem `overId` がアイテムのIDであるか
 * @property overCategoryId ドロップ先のカテゴリーID (アイテムの場合でもその親カテゴリーID)
 */
export interface IDragOverTargetReturnData {
    isOverItem: boolean;
    overCategoryId: string | null;
}

/**
 * DND移動に必要な情報返却データ型
 * @property activeCategoryId ドラッグ中のアイテムのカテゴリーID
 * @property overCategoryId ドロップ先のカテゴリーID
 * @property activeItem ドラッグ中のアイテムオブジェクト
 */
export interface IDndMoveReturnData<
    C extends { id: string },
    I extends { id: string },
> {
    activeDataset: ICategoryWithItems<C, I>;
    overDataset: ICategoryWithItems<C, I>;
    activeItem: I;
    isOverItem: boolean;
}
