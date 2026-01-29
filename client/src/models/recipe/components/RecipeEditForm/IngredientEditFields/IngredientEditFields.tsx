'use client';
import React from 'react';
import { GrippableHorizontalItem, TextButton } from '@/components';
import { Control, useFieldArray, useFormContext } from 'react-hook-form';
import { ChevronRight } from 'lucide-react';
import { COLOR_VARIANT, TMP_ID_PREFIX } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { useDialog } from '@/hooks/useDialog';
import { IngredientCategoryEditForm } from '@/components/dialog-contents';
import { closestCenter, DndContext, DragOverlay } from '@dnd-kit/core';
import IngredientItemList from './IngredientItemList';
import { defaultIngredientItem } from '@/models/ingredient/constants';
import { createDefaultData } from '@/utils';
import { IIngredientItem } from '@/types/api';
import { arrayMove } from '@dnd-kit/sortable';
import { getItemsInCategory } from '@/utils';
import { useItemAndCategoryDnd } from '@/hooks/useItemAndCategoryDnd';
import { RecipeEditFormData } from '@/models/recipe/types';
import { DialogField } from '@/components/form-fields';
import { formatIngredient } from '@/utils/format';

interface Props {
    control: Control<RecipeEditFormData>;
}

const IngredientEditFields = ({ control }: Props) => {
    const prefix = TMP_ID_PREFIX.INGREDIENT_ITEM;
    const { categories } = useIngredientStore();
    const { openDialog } = useDialog();
    const [tmpItems, setTmpItems] = React.useState<IIngredientItem[]>([]);
    const dndContextId = React.useId();
    const { getValues, watch } = useFormContext<RecipeEditFormData>();
    const { replace, update, remove } = useFieldArray<
        RecipeEditFormData,
        'ingredients'
    >({
        control,
        name: 'ingredients',
    });
    const watchFields = watch('ingredients');

    /**
     * ドラッグオーバー
     */
    const customHandleDragOver = React.useCallback(
        (
            activeId: string,
            activeItem: IIngredientItem,
            overCategoryId: string,
        ) => {
            // 別カテゴリーへの移動の場合
            if (activeItem.categoryId !== overCategoryId) {
                // 移動元のカテゴリーに属するアイテムを取得
                const itemsInCategory = getItemsInCategory(
                    tmpItems,
                    activeItem.categoryId,
                );

                // 移動元のカテゴリーにアイテムがなくなった場合、空の食材を追加
                const updatedItems =
                    itemsInCategory.length <= 1
                        ? createItemsWithEmpty(activeItem.categoryId, tmpItems)
                        : tmpItems;

                // tmpItemsを更新
                setTmpItems(
                    updatedItems.map(v =>
                        v.id === activeId
                            ? {
                                ...activeItem,
                                categoryId: overCategoryId,
                            }
                            : v,
                    ),
                );
            }
        },
        [tmpItems, categories],
    );

    /**
     * ドラッグ終了
     */
    const customHandleDragEnd = React.useCallback(
        (activeIndex: number | undefined, overIndex: number | undefined) => {
            // 並び替えたtmpItemsを更新
            const array =
                activeIndex !== undefined && overIndex !== undefined
                    ? arrayMove(tmpItems, activeIndex, overIndex)
                    : tmpItems;
            replace(array);
        },
        [tmpItems],
    );

    const {
        activeId,
        sensors,
        activeItem,
        activeCategory,
        handleDragStart,
        handleDragOver,
        handleDragEnd,
    } = useItemAndCategoryDnd({
        currentItems: tmpItems,
        categories,
        onDragOver: customHandleDragOver,
        onDragEnd: customHandleDragEnd,
    });

    /**
     * 空の材料を含むアイテムリストを生成
     */
    const createItemsWithEmpty = React.useCallback(
        (categoryId: string, currentItems?: IIngredientItem[]) => {
            const itemsToUse = currentItems || tmpItems;
            // 空の食材を作成
            const emptyItem = {
                ...createDefaultData(defaultIngredientItem, prefix),
                categoryId: categoryId,
            };

            // カテゴリーに属する食材を整理し、空の食材を追加
            const newItems = categories
                .map(category => {
                    const itemsInCategory = getItemsInCategory(
                        itemsToUse,
                        category.id,
                    );
                    return category.id === categoryId
                        ? [...itemsInCategory, emptyItem]
                        : itemsInCategory;
                })
                .flat();
            return newItems;
        },
        [categories, tmpItems],
    );

    /**
     * 空の食材を追加
     */
    const addEmptyItem = React.useCallback(
        (categoryId: string) => {
            const items = createItemsWithEmpty(categoryId, tmpItems);
            replace(items);
            return items;
        },
        [tmpItems],
    );

    /**
     * 食材を更新
     */
    const updateItem = React.useCallback(
        (index: number, item: IIngredientItem) => {
            update(index, item);
        },
        [tmpItems],
    );

    /**
     * 食材を削除
     */
    const removeItem = React.useCallback(
        (index: number) => {
            const categoryId = tmpItems[index].categoryId;

            // カテゴリーに属する食材が1つの場合、空の食材を追加
            if (tmpItems.filter(v => v.categoryId === categoryId).length <= 1) {
                update(index, {
                    ...defaultIngredientItem,
                    categoryId: categoryId,
                });
            } else {
                // 食材を削除
                remove(index);
            }
        },
        [tmpItems],
    );

    /**
     * tmpItemsをfieldsの内容で更新
     */
    React.useEffect(() => {
        const ingredients = getValues('ingredients');
        if (categories.length > 0) {
            // 各カテゴリーに属する食材を整理し、必要に応じて空の食材を追加
            const filledItems: IIngredientItem[] = categories
                .map(category => {
                    const itemsInCategory = getItemsInCategory(
                        ingredients,
                        category.id,
                    );
                    // カテゴリーに属する食材が存在する場合、それらをそのまま使用
                    if (itemsInCategory.length > 0) {
                        return itemsInCategory;
                    } else {
                        // カテゴリーに属する食材がない場合、新しい空の食材を追加
                        return [
                            {
                                ...createDefaultData(
                                    defaultIngredientItem,
                                    prefix,
                                ),
                                categoryId: category.id,
                            },
                        ];
                    }
                })
                .flat();
            replace(filledItems);
            setTmpItems(filledItems);
        }
    }, [categories]);

    /**
     * ドラッグ中でない場合、tmpItemsをfieldsの内容で更新
     * @returns void
     */
    React.useEffect(() => {
        if (!activeId) {
            setTmpItems(watchFields);
        }
    }, [watchFields, activeId]);

    return (
        <div className="flex flex-col gap-y-5">
            <div className="flex flex-col gap-y-2">
                {tmpItems && tmpItems.length > 0 && (
                    <DndContext
                        id={dndContextId}
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragStart={handleDragStart}
                        onDragOver={handleDragOver}
                        onDragEnd={handleDragEnd}>
                        {categories.map(category => {
                            const items = getItemsInCategory(
                                tmpItems,
                                category.id,
                            );
                            const itemsKey = items
                                .map(item => item.id)
                                .join(',');
                            const offsetIndex = tmpItems.indexOf(items[0]);
                            return (
                                <IngredientItemList
                                    key={`${category.id}-${itemsKey}`}
                                    category={category}
                                    items={items}
                                    addEmptyItem={() =>
                                        addEmptyItem(category.id)
                                    }
                                    removeItem={(index: number) =>
                                        removeItem(offsetIndex + index)
                                    }
                                    updateItem={(
                                        index: number,
                                        item: IIngredientItem,
                                    ) => updateItem(offsetIndex + index, item)}
                                />
                            );
                        })}
                        <DragOverlay>
                            {activeItem && (
                                <GrippableHorizontalItem
                                    hasDeleteButton={true}
                                    isDisabledDeleteButton={true}
                                    onDelete={() => { }}>
                                    <DialogField
                                        value={formatIngredient(activeItem)}
                                        placeholder={`${activeCategory?.name}を設定`}
                                        onOpenDialog={() => { }}
                                    />
                                </GrippableHorizontalItem>
                            )}
                        </DragOverlay>
                    </DndContext>
                )}
            </div>
            <TextButton
                colorVariant={COLOR_VARIANT.SECONDARY}
                onClick={() => {
                    openDialog({
                        title: '材料カテゴリーを設定',
                        children: () => <IngredientCategoryEditForm />
                    });
                }}>
                材料カテゴリーの追加・編集
                <ChevronRight size={20} />
            </TextButton>
        </div>
    );
};

export default IngredientEditFields;
