'use client';
import { DndSortableList, TextButton } from '@/components/common';
import EditDialogButton from '../EditDialogButton/EditDialogButton';
import {
    defaultIngredient,
    RECIPE_SETTING_DIALOG_EDIT_MODE,
    RECIPE_SETTING_DIALOG_NAME,
    TMP_ID_PREFIX,
} from '../../constants';
import { useRecipeStore } from '../../hooks/recipeStores';
import { Control, useFieldArray } from 'react-hook-form';
import { IIngredient, IPostRecipeRequest } from '@/types/api/recipe';
import { CirclePlus } from 'lucide-react';

interface Props {
    ingredients: IIngredient[];
    control: Control<IPostRecipeRequest>;
}

const IngredientEditFields = ({ ingredients, control }: Props) => {
    const { ingredientUnits, openDialog } = useRecipeStore();
    const { fields, append, remove, update, move } =
        useFieldArray<IPostRecipeRequest>({
            control,
            name: 'ingredients',
        });

    /**
     * 空の食材を追加
     */
    const addEmptyIngredient = () => {
        let lastIndex = ingredients?.length ?? 0;
        const emptyItem = ingredients?.filter(item => item.name === '');

        // 空の食材がある場合は、その食材のインデックスを取得
        if (emptyItem && emptyItem.length > 0) {
            lastIndex =
                ingredients?.findIndex(item => item.id === emptyItem[0].id) ??
                0;
        }
        // 空の食材がない場合は、新しい入力項目を追加
        else {
            append(defaultIngredient);
        }

        // 食材の編集ダイアログを開く
        openDialog(RECIPE_SETTING_DIALOG_NAME.INGREDIENT, {
            item: undefined,
            editMode: RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE,
            onAction: (value: IIngredient) => update(lastIndex, value),
        });
    };

    /**
     * 食材を削除
     * @param index 削除する食材のインデックス
     */
    const removeItem = (index: number) => {
        // 最初の食材を削除した場合は、デフォルトの食材を設定（入力項目が0個にならないようにするため）
        if (index === 0) {
            update(0, defaultIngredient);
        } else {
            remove(index);
        }
    };

    /**
     * 食材の値のフォーマット
     * @param value 食材のデータ
     * @returns 食材の値
     */
    const formatValue = (value: IIngredient) => {
        let result: string = value.name;

        if (result && value.unitId) {
            result += ` / ${value.quantity || ''}${ingredientUnits.find(v => v.id === value.unitId)?.name}`;
        }

        return result;
    };

    return (
        <div className="flex flex-col gap-y-1">
            <div className="text-base">食材</div>
            <DndSortableList
                items={fields}
                prefix={TMP_ID_PREFIX.RECIPE_INGREDIENT}
                onDragEnd={(oldIndex, newIndex) => {
                    move(oldIndex, newIndex);
                }}
                renderItem={(item, index) => {
                    return (
                        <EditDialogButton
                            key={item.id}
                            dialogName={RECIPE_SETTING_DIALOG_NAME.INGREDIENT}
                            editMode={RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE}
                            isDisabled={
                                index === 0 &&
                                ingredients?.length === 1 &&
                                ingredients[0].name === ''
                            }
                            name={`ingredients.${index}`}
                            placeholder="食材を設定"
                            control={control}
                            onDelete={() => {
                                removeItem(index);
                            }}
                            formatValue={formatValue}
                        />
                    );
                }}
            />
            <TextButton
                type="button"
                onClick={addEmptyIngredient}
                className="!border-none !bg-transparent hover:!bg-gray-light">
                <CirclePlus size={20} />
                追加
            </TextButton>
        </div>
    );
};

export default IngredientEditFields;
