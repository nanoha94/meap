'use client';
import { TextButton } from '@/components/common';
import { DndSortableList } from '@/components/dnd';
import EditDialogButton from '../EditDialogButton/EditDialogButton';
import { Control, useFieldArray, useWatch } from 'react-hook-form';
import { IPostRecipeRequest } from '@/types/api/recipe';
import { ChevronRight, CirclePlus } from 'lucide-react';
import React from 'react';
import { IIngredient } from '@/types/api/ingredient';
import { defaultIngredient } from '@/models/ingredient/constants';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';

interface Props {
    control: Control<IPostRecipeRequest>;
}

const IngredientEditFields = ({ control }: Props) => {
    const { ingredientUnits, openDialog } = useIngredientStore();
    const { fields, append, remove, update, move } =
        useFieldArray<IPostRecipeRequest>({
            control,
            name: 'ingredients',
        });
    const ingredients = useWatch({ control, name: 'ingredients' });
    const prefix: string = TMP_ID_PREFIX.INGREDIENT;

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
            append({ ...defaultIngredient, id: `${prefix}${Date.now()}` });
        }

        // 食材の編集ダイアログを開く
        openDialog(DIALOG_NAME.INGREDIENT_ADD_EDIT, {
            item: undefined,
            editMode: DIALOG_EDIT_MODE.CREATE,
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
        <div className="flex flex-col gap-y-5">
            <div className="flex flex-col gap-y-2">
                <div>食材</div>
                <DndSortableList
                    items={fields}
                    prefix={TMP_ID_PREFIX.INGREDIENT}
                    onDragEnd={(oldIndex, newIndex) => {
                        move(oldIndex, newIndex);
                    }}
                    renderItem={(item, index) => {
                        return (
                            <EditDialogButton
                                key={item.id}
                                dialogName={DIALOG_NAME.INGREDIENT_ADD_EDIT}
                                editMode={DIALOG_EDIT_MODE.UPDATE}
                                isDisabled={
                                    index === 0 &&
                                    fields?.length === 1 &&
                                    fields[0].name === ''
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
            <TextButton
                colorVariant="secondary"
                onClick={() =>
                    openDialog(DIALOG_NAME.INGREDIENT_CATEGORY_SETTING, {
                        onAction: () => {},
                    })
                }>
                材料カテゴリーの追加・編集
                <ChevronRight size={20} />
            </TextButton>
        </div>
    );
};

export default IngredientEditFields;
