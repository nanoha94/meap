import { IIngredientCategory } from '@/types/api/ingredient';
import { IngredientSettingDialogConfigs } from './types';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';

/* ダイアログ設定 */
export const INGREDIENT_SETTING_DIALOG_CONFIGS: IngredientSettingDialogConfigs =
    {
        [DIALOG_NAME.INGREDIENT_ADD_EDIT]: {
            [DIALOG_EDIT_MODE.CREATE]: {
                title: '食材を追加',
                actionButtonText: '追加',
            },
            [DIALOG_EDIT_MODE.UPDATE]: {
                title: '食材を編集',
                actionButtonText: '保存',
            },
        },
        [DIALOG_NAME.INGREDIENT_CATEGORY_SETTING]: {
            title: '材料カテゴリーを設定',
            actionButtonText: '設定',
        },
    };

/**
 * 食材のデフォルト設定
 */
export const defaultIngredient = {
    name: '',
    quantity: null,
    unitId: '',
    categoryId: '',
};

/**
 * 食材のデフォルト設定
 */
export const defaultIngredientCategory: IIngredientCategory = {
    id: `${TMP_ID_PREFIX.INGREDIENT_CATEGORY}-0`,
    name: '',
    order: 0,
};
