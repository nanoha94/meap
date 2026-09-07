'use client';
import React from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { closestCenter, DndContext, DragOverlay } from '@dnd-kit/core';
import { ChevronRight } from 'lucide-react';
import { Control, useFieldArray, useFormContext } from 'react-hook-form';

import {
    DialogField,
    GrippableHorizontalItem,
    IngredientCategoryEditForm,
    TextButton,
} from '@/components';
import { TMP_ID_PREFIX } from '@/constants';
import { useDialog, useItemAndCategoryDnd } from '@/hooks';
import { defaultIngredientItem, formatIngredient } from '@/models/ingredient';
import { RecipeEditFormData } from '@/models/recipe/types';
import { IIngredientCategory, IIngredientItem } from '@/types';
import { createDefaultData, getItemsInCategory } from '@/utils';
import IngredientItemList from './IngredientItemList';

interface Props {
    control: Control<RecipeEditFormData>;
    errors: Record<string, string> | null;
}

const IngredientEditFields = ({ control, errors }: Props) => {
    // constant value
    const prefix = TMP_ID_PREFIX.INGREDIENT_ITEM;

    // hook
    const { openDialog } = useDialog();
    /** ドラッグ中のみ setTmpItems で上書き。null のときは watch と同一（effect で watch に追従させない） */
    const [tmpItemsDrag, setTmpItems] =
        React.useState<IIngredientItem[] | null>(null);
    const dndContextId = React.useId();
    const { getValues, setValue, watch } = useFormContext<RecipeEditFormData>();
    const { replace, update, remove } = useFieldArray<
        RecipeEditFormData,
        'ingredients'
    >({
        control,
        name: 'ingredients',
    });
    const watchFields = watch('ingredients') ?? [];
    const watchedIngredientCategories = watch('ingredientCategories');
    const ingredientCategories = React.useMemo(
        () => watchedIngredientCategories ?? [],
        [watchedIngredientCategories],
    );
    const tmpItems = tmpItemsDrag ?? watchFields;

    /**
     * カテゴリー編集ダイアログで保存した内容を親フォームへ反映する
     */
    const handleSaveIngredientCategories = React.useCallback(
        (newCategories: IIngredientCategory[]) => {
            const newCategoryIds = new Set(newCategories.map(category => category.id));
            const defaultCategory =
                newCategories.find(category => category.isDefault)
                ?? newCategories[0];

            const currentIngredients = getValues('ingredients') ?? [];
            const reassignedIngredients = currentIngredients.map(ingredient => {
                if (newCategoryIds.has(ingredient.categoryId)) {
                    return ingredient;
                }
                return {
                    ...ingredient,
                    categoryId: defaultCategory?.id ?? ingredient.categoryId,
                };
            });

            setValue('ingredientCategories', newCategories);
            replace(reassignedIngredients);
        },
        [getValues, replace, setValue],
    );

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
            const newItems = ingredientCategories
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
        [ingredientCategories, tmpItems, prefix],
    );

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

                // ドラッグ中の表示用リストを更新
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
        [tmpItems, createItemsWithEmpty],
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
            setTmpItems(null);
        },
        [tmpItems, replace],
    );

    const {
        sensors,
        activeItem,
        activeCategory,
        handleDragStart,
        handleDragOver,
        handleDragEnd,
    } = useItemAndCategoryDnd({
        currentItems: tmpItems,
        categories: ingredientCategories,
        onDragOver: customHandleDragOver,
        onDragEnd: customHandleDragEnd,
    });

    /**
     * 空の食材を追加
     */
    const addEmptyItem = React.useCallback(
        (categoryId: string) => {
            const items = createItemsWithEmpty(categoryId, tmpItems);
            replace(items);
            return items;
        },
        [createItemsWithEmpty, replace, tmpItems],
    );

    /**
     * 食材を更新
     */
    const updateItem = React.useCallback(
        (index: number, item: IIngredientItem) => {
            update(index, item);
        },
        [update],
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
        [tmpItems, remove, update],
    );

    /**
     * カテゴリ構成に合わせて空行を補い、フォーム値を replace（tmpItems は watch から導出）
     */
    React.useEffect(() => {
        const ingredients = getValues('ingredients');
        if (ingredientCategories.length > 0) {
            // 各カテゴリーに属する食材を整理し、必要に応じて空の食材を追加
            const filledItems: IIngredientItem[] = ingredientCategories
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
        }
    }, [ingredientCategories, getValues, prefix, replace]);

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
                        {ingredientCategories.map(category => {
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
                                    errors={localIndex => {
                                        const idx = offsetIndex + localIndex;
                                        return (
                                            errors?.[`ingredients.${idx}.name`]
                                            ?? errors?.[
                                            `ingredients.${idx}.quantityDisplay`
                                            ]
                                            ?? ''
                                        );
                                    }}
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
                                        hasError={false}
                                        onOpenDialog={() => { }}
                                    />
                                </GrippableHorizontalItem>
                            )}
                        </DragOverlay>
                    </DndContext>
                )}
            </div>
            <TextButton
                onClick={() => {
                    openDialog({
                        title: '材料カテゴリーを設定',
                        children: (
                            <IngredientCategoryEditForm
                                categories={ingredientCategories}
                                onSave={handleSaveIngredientCategories}
                            />
                        ),
                    });
                }}>
                材料カテゴリーの追加・編集
                <ChevronRight size={20} />
            </TextButton>
        </div>
    );
};

export default IngredientEditFields;
