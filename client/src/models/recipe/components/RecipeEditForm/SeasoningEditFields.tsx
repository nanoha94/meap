'use client';
import { TextButton } from '@/components/common';
import { DndSortableList } from '@/components/dnd';
import EditDialogButton from '../EditDialogButton/EditDialogButton';
import {
    defaultSeasoning,
    RECIPE_SETTING_DIALOG_EDIT_MODE,
    RECIPE_SETTING_DIALOG_NAME,
    TMP_ID_PREFIX,
} from '../../constants';
import { useRecipeStore } from '../../hooks/recipeStores';
import { Control, useFieldArray } from 'react-hook-form';
import { IPostRecipeRequest, ISeasoning } from '@/types/api/recipe';
import { CirclePlus } from 'lucide-react';

interface Props {
    control: Control<IPostRecipeRequest>;
}

const SeasoningEditFields = ({ control }: Props) => {
    const { seasoningUnits, openDialog } = useRecipeStore();
    const { fields, append, remove, update, move } =
        useFieldArray<IPostRecipeRequest>({
            control,
            name: 'seasonings',
        });

    /**
     * 空の調味料を追加
     */
    const addEmptySeasoning = () => {
        let lastIndex = fields?.length ?? 0;
        const emptyItem = fields?.filter(item => item.name === '');

        // 空の調味料がある場合は、その調味料のインデックスを取得
        if (emptyItem && emptyItem.length > 0) {
            lastIndex =
                fields?.findIndex(item => item.id === emptyItem[0].id) ?? 0;
        }
        // 空の調味料がない場合は、新しい入力項目を追加
        else {
            append(defaultSeasoning);
        }

        // 調味料の編集ダイアログを開く
        // TODO: ダイアログのモードを切り替える
        openDialog(RECIPE_SETTING_DIALOG_NAME.SEASONING, {
            item: undefined,
            editMode: RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE,
            onAction: (value: ISeasoning) => update(lastIndex, value),
        });
    };

    /**
     * 調味料を削除
     * @param index 削除する調味料のインデックス
     */
    const removeItem = (index: number) => {
        // 最初の調味料を削除した場合は、デフォルトの調味料を設定（入力項目が0個にならないようにするため）
        if (index === 0) {
            update(0, defaultSeasoning);
        } else {
            remove(index);
        }
    };

    /**
     * 調味料の値のフォーマット
     * @param value 調味料のデータ
     * @returns 調味料の値
     */
    const formatValue = (value: ISeasoning) => {
        let result: string = value.name;

        if (result && value.unitId) {
            result += ` / ${value.quantity || ''}${seasoningUnits.find(v => v.id === value.unitId)?.name}`;
        }

        return result;
    };

    return (
        <div className="flex flex-col gap-y-2">
            <div className="text-base">調味料</div>
            <DndSortableList
                items={fields}
                prefix={TMP_ID_PREFIX.RECIPE_SEASONING}
                onDragEnd={(oldIndex, newIndex) => {
                    move(oldIndex, newIndex);
                }}
                renderItem={(item, index) => {
                    return (
                        <EditDialogButton
                            key={item.id}
                            dialogName={RECIPE_SETTING_DIALOG_NAME.SEASONING}
                            editMode={RECIPE_SETTING_DIALOG_EDIT_MODE.UPDATE}
                            isDisabled={
                                index === 0 &&
                                fields?.length === 1 &&
                                fields[0].name === ''
                            }
                            name={`seasonings.${index}`}
                            placeholder="調味料を設定"
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
                onClick={addEmptySeasoning}
                className="!border-none !bg-transparent hover:!bg-gray-light">
                <CirclePlus size={20} />
                追加
            </TextButton>
        </div>
    );
};

export default SeasoningEditFields;
