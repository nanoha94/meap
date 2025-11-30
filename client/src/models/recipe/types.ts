import { DIALOG_NAME } from '@/constants';
import { IRecipe, IRecipeStep, IImageWithFile } from '@/types/api';

export type RecipeSettingDialogConfig = {
    title: string;
    actionButtonText: string;
};

export type RecipeSettingDialogConfigs = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: RecipeSettingDialogConfig;
};

// レシピ編集画面のフォーム型
// 画像ファイルを管理できるようにする
export type RecipeEditFormData = IRecipe & {
    thumbnail: IImageWithFile | null;
    steps: RecipeStepEditFormData[];
};

export type RecipeStepEditFormData = IRecipeStep & {
    image: IImageWithFile | null;
};
