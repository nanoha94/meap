import { IImageWithFile, IRecipe, IRecipeStep } from '@/types';

// レシピ編集画面のフォーム型
// thumbnail/steps を編集用の型に差し替える
export type RecipeEditFormData = Omit<IRecipe, 'thumbnail' | 'steps' | 'lastPlannedDate'> & {
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
    categoryIds?: string[];
    lastPlannedDateFrom?: string;
    lastPlannedDateTo?: string;
}
