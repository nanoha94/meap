import { DIALOG_NAME } from '@/constants';
import { IImageWithFile, IRecipe, IRecipeStep } from '@/types';

export type RecipeSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: RecipeSettingDialogConfig;
};

// レシピ編集画面のフォーム型（thumbnail/steps を編集用の型に差し替え）
export type RecipeEditFormData = Omit<IRecipe, 'thumbnail' | 'steps'> & {
    thumbnail: IImageWithFile | null;
    steps: RecipeStepEditFormData[];
};

// レシピ手順編集画面のフォーム型 (imageを編集用の型に差し替える)
export type RecipeStepEditFormData = Omit<IRecipeStep, 'image'> & {
    image: IImageWithFile | null;
};
