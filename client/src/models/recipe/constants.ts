import { TMP_ID_PREFIX } from '@/constants';
import { AlertDialogConfig, IRecipeCategory } from '@/types';
import { RecipeEditFormData, RecipeStepEditFormData } from './types';

export const RECIPES_PER_PAGE = 20;

// アラートダイアログの設定
export const RECIPE_ALERT_DIALOG_CONFIGS = {
    // レシピを削除
    deleteItem: (name: string): AlertDialogConfig => ({
        title: '削除',
        message: [`${name}を削除しますか？`],
        alertMessage: '',
        actionButtonText: '削除',
    }),
};

/** デフォルト設定 */
export const DEFAULT_RECIPE_CATEGORY: IRecipeCategory = {
    id: `${TMP_ID_PREFIX.RECIPE_CATEGORY}${Date.now()}`,
    name: '',
    order: 0,
};

export const DEFAULT_RECIPE_STEP: RecipeStepEditFormData = {
    id: `${TMP_ID_PREFIX.RECIPE_STEP}${Date.now()}`,
    instruction: '',
    image: {
        file: null,
        src: '',
        width: 0,
        height: 0,
    },
    order: 0,
};

export const DEFAULT_RECIPE_EDIT_FORM_DATA: RecipeEditFormData = {
    id: '',
    name: '',
    url: '',
    memo: '',
    servingCount: null,
    thumbnail: null,
    categories: [],
    ingredients: [],
    steps: [],
    cookingTime: null,
};

/**
 * 並び替えオプション
 */
export const sortOptions: { id: string; name: string, sort: string, order: string }[] = [
    { id: 'created_at_newest', name: '作成日が新しい順', sort: 'created_at', order: 'desc' },
    { id: 'created_at_oldest', name: '作成日が古い順', sort: 'created_at', order: 'asc' },
    { id: 'meal_plan_date_newest', name: '前回の献立日が新しい順', sort: 'last_planned_date', order: 'desc' },
    { id: 'meal_plan_date_oldest', name: '前回の献立日が古い順', sort: 'last_planned_date', order: 'asc' },
    // { id: 'name_asc', name: '名前順', sort: 'name', order: 'asc' },
    // 漢字の並び替えにはカナをDBに登録する必要があるため、保留（TODO: UI要検討）
];
