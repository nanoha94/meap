import { IImageWithFile, IRecipe, IRecipeStep } from '@/types';

// レシピ編集画面のフォーム型
// ownerUserId はuseStateで管理するため、ここでは不要
// thumbnail/steps を編集用の型に差し替える
export type RecipeEditFormData = Omit<IRecipe, 'ownerUserId' | 'thumbnail' | 'steps' | 'lastPlannedDate'> & {
    thumbnail: IImageWithFile | null;
    steps: RecipeStepEditFormData[];
};

// レシピ手順編集画面のフォーム型 (imageを編集用の型に差し替える)
export type RecipeStepEditFormData = Omit<IRecipeStep, 'image'> & {
    image: IImageWithFile | null;
};

// レシピ一覧取得画面のフォーム型
export interface RecipeFilterFormData {
    recipeName?: string;
    ingredientName?: string;
    categoryId?: string;       // TODO: ひとまずはカテゴリ１つとしておく。後で配列に変更する
    lastPlannedDateFrom?: string;
    lastPlannedDateTo?: string;
}
