'use client';
import React from 'react';
import { CirclePlus } from 'lucide-react';
import { Control, useFieldArray, useWatch } from 'react-hook-form';

import { DndSortableList, TextButton } from '@/components';
import { BUTTON_TYPE, DND_SORTABLE_LIST_TYPE, TMP_ID_PREFIX } from '@/constants';
import { useFocusItem } from '@/hooks';
import { DEFAULT_RECIPE_STEP } from '@/models/recipe/constants';
import { RecipeEditFormData } from '@/models/recipe/types';
import { createDefaultData, focusItemById } from '@/utils';
import StepEditItem from './StepEditItem';

interface Props {
    control: Control<RecipeEditFormData>;
    errors: Record<string, string> | null;
}

const StepEditFields = ({ control, errors }: Props) => {
    const prefix = TMP_ID_PREFIX.RECIPE_STEP;
    const { fields, move, remove, replace, append, update } = useFieldArray<
        RecipeEditFormData,
        'steps'
    >({
        control,
        name: 'steps',
    });
    const watchSteps = useWatch({ control, name: 'steps' });
    const { setFocusTargetId } = useFocusItem(watchSteps);

    /**
     * 空の手順を追加
     */
    const addEmptyItem = React.useCallback(() => {
        const emptyItemInList = watchSteps.filter(
            item => item.instruction === '' && item.image?.src.length === 0,
        );

        // 空のアイテムがある場合
        if (emptyItemInList.length > 0) {
            // 最初の空アイテムにフォーカスを当てる
            if (emptyItemInList[0].id) {
                focusItemById(emptyItemInList[0].id);
            }
        }
        // 空のアイテムがない場合
        else {
            // 空のアイテムを作成して、フォーカスを当てる
            const newItem = createDefaultData(DEFAULT_RECIPE_STEP, prefix);
            append(newItem);
            setFocusTargetId(newItem.id);
        }
    }, [watchSteps, prefix, append, setFocusTargetId]);

    /**
     * 手順を削除
     */
    const removeItem = React.useCallback(
        (index: number) => {
            // 削除するステップの画像にobjectURLが含まれている場合、解放する
            const removedStep = watchSteps[index];
            if (removedStep?.image?.file && removedStep?.image?.src) {
                URL.revokeObjectURL(removedStep.image.src);
            }

            // 手順が1件の場合、空データを設定
            if (watchSteps.length <= 1) {
                update(index, {
                    ...DEFAULT_RECIPE_STEP,
                    id: removedStep.id,
                });
            }
            // 削除
            else {
                remove(index);
            }
        },
        [watchSteps, remove, update],
    );

    /**
     * 手順が1件の場合、空データを設定
     */
    React.useEffect(() => {
        if (fields.length <= 0) {
            replace([createDefaultData(DEFAULT_RECIPE_STEP, prefix)]);
        }
    }, [fields, prefix, replace]);

    return (
        <>
            <div className="flex flex-col gap-y-2">
                <div>手順</div>
                <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] gap-3">
                    <DndSortableList
                        type={DND_SORTABLE_LIST_TYPE.GRID}
                        items={fields}
                        prefix={prefix}
                        onDragEnd={(oldIndex, newIndex) =>
                            move(oldIndex, newIndex)
                        }
                        renderItem={(item, index) =>
                            watchSteps[index] && (
                                <StepEditItem
                                    control={control}
                                    index={index}
                                    item={watchSteps[index]}
                                    onDelete={() => removeItem(index)}
                                    isDisabledDeleteButton={
                                        index === 0 &&
                                        watchSteps.length === 1 &&
                                        item.instruction === '' &&
                                        item.image?.src.length === 0
                                    }
                                    errorMessage={
                                        errors?.[`steps.${index}`] ?? ''
                                    }
                                />
                            )
                        }
                    />
                </div>
                <TextButton
                    type={BUTTON_TYPE.BUTTON}
                    onClick={addEmptyItem}
                    className="!border-none !bg-transparent hover:!bg-gray-light">
                    <CirclePlus size={20} />
                    手順を追加
                </TextButton>
            </div>
        </>
    );
};

export default StepEditFields;
