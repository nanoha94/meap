'use client';
import React from 'react';
import { TextButton } from '@/components/common';
import { Control, useFieldArray, useFormContext } from 'react-hook-form';
import { IRecipe } from '@/types/api/recipe';
import { ChevronRight } from 'lucide-react';
import { DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';
import {
    closestCenter,
    DndContext,
    DragOverEvent,
    DragOverlay,
    DragStartEvent,
    DragEndEvent,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import IngredientItemList from './IngredientItemList';
import { defaultIngredientItem } from '@/models/ingredient/constants';
import { createDefaultData } from '@/utils';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { IIngredientItem } from '@/types/api/ingredient';
import {
    DRAG_ACTIVATION_DISTANCE,
    TOUCH_ACTIVATION_DELAY,
    TOUCH_ACTIVATION_TOLERANCE,
} from '@/constants';
import IngredientEditDialogButton from './IngredientEditDialogButton/IngredientEditDialogButton';
import { arrayMove } from '@dnd-kit/sortable';

interface Props {
    control: Control<IRecipe>;
}

const IngredientEditFields = ({ control }: Props) => {
    const { categories, openDialog } = useIngredientStore();
    const [activeId, setActiveId] = React.useState<string | null>(null);
    const [tmpItems, setTmpItems] = React.useState<IIngredientItem[]>([]);
    const dndContextId = React.useId();
    const { getValues, watch } = useFormContext<IRecipe>();
    const { replace, update, remove } = useFieldArray<IRecipe, 'ingredients'>({
        control,
        name: 'ingredients',
    });
    const watchFields = watch('ingredients');

    // ドラッグ&ドロップ設定
    const sensors = useSensors(
        useSensor(MouseSensor, {
            // マウス操作の誤クリックを防ぐため、一定距離移動するまでドラッグを開始しない
            activationConstraint: {
                distance: DRAG_ACTIVATION_DISTANCE,
            },
        }),
        useSensor(TouchSensor, {
            // タッチ操作の誤操作を防ぐため、250msの遅延と5pxの許容範囲を設定
            activationConstraint: {
                delay: TOUCH_ACTIVATION_DELAY,
                tolerance: TOUCH_ACTIVATION_TOLERANCE,
            },
        }),
    );

    /**
     * アクティブなアイテム
     */
    const activeItem = React.useMemo(
        () => tmpItems.find(item => item.id === activeId),
        [activeId, tmpItems],
    );

    /**
     * アクティブなカテゴリー
     */
    const activeCategory = React.useMemo(() => {
        if (!activeItem) return null;
        return categories.find(
            category => category.id === activeItem.categoryId,
        );
    }, [activeItem, categories]);

    /**
     * ドラッグ開始
     */
    const handleDragStart = React.useCallback((event: DragStartEvent) => {
        setActiveId(event.active.id as string);
    }, []);

    /**
     * 空の食材を含むアイテムリストを生成
     */
    const createItemsWithEmpty = React.useCallback(
        (categoryId: string, currentItems?: IIngredientItem[]) => {
            const itemsToUse = currentItems || tmpItems;
            // 空の食材を作成
            const emptyItem = {
                ...createDefaultData(
                    defaultIngredientItem,
                    TMP_ID_PREFIX.INGREDIENT_ITEM,
                ),
                categoryId: categoryId,
            };

            // カテゴリーに属する食材を整理し、空の食材を追加
            const newItems = categories
                .map(category => {
                    const itemsInCategory = itemsToUse.filter(
                        item => item.categoryId === category.id,
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
                addEmptyItem(categoryId);
            }

            // 食材を削除
            remove(index);
        },
        [tmpItems],
    );

    /**
     * ドラッグオーバー
     */
    const handleDragOver = React.useCallback(
        (event: DragOverEvent) => {
            const { active, over } = event;
            if (!over || active.id === over.id) return;

            // ドラッグ中のアイテムとドロップ先のアイテムを取得
            const activeItem = tmpItems.find(item => item.id === active.id);
            const overItem = tmpItems.find(item => item.id === over.id);

            if (!activeItem || !overItem) return;

            // ドロップ先のカテゴリーIDを取得
            const targetCategoryId = overItem.categoryId;
            // ドラッグ中のアイテムのインデックスを取得
            const activeIndex = tmpItems.findIndex(
                item => item.id === active.id,
            );
            // ドロップ先のアイテムのインデックスを取得
            const overIndex = tmpItems.findIndex(item => item.id === over.id);

            // ドラッグ中のアイテムのインデックスまたはドロップ先のアイテムのインデックスが見つからない場合、処理を終了
            if (activeIndex === -1 || overIndex === -1) return;

            // 別カテゴリーへの移動の場合
            if (activeItem.categoryId !== targetCategoryId) {
                // 移動元のカテゴリーに属するアイテムを取得
                const itemsInCategory = tmpItems.filter(
                    item => item.categoryId === activeItem.categoryId,
                );

                // 移動元のカテゴリーにアイテムがなくなった場合、空の食材を追加
                const updatedItems =
                    itemsInCategory.length <= 1
                        ? createItemsWithEmpty(activeItem.categoryId, tmpItems)
                        : tmpItems;

                // tmpItemsを更新
                setTmpItems(
                    updatedItems.map(v =>
                        v.id === active.id
                            ? {
                                  ...activeItem,
                                  categoryId: targetCategoryId,
                              }
                            : v,
                    ),
                );
            }
        },
        [tmpItems],
    );

    /**
     * ドラッグ終了
     */
    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;

            if (active && over && active.id !== over.id) {
                const activeIndex = tmpItems.findIndex(
                    item => item.id === active.id,
                );
                const overIndex = tmpItems.some(v => v.id === over.id)
                    ? tmpItems.findIndex(item => item.id === over.id)
                    : tmpItems.findIndex(v => v.categoryId === over.id);

                if (activeIndex !== -1 && overIndex !== -1) {
                    replace(arrayMove(tmpItems, activeIndex, overIndex));
                }
            } else {
                replace(tmpItems);
            }
            setActiveId(null);
        },
        [tmpItems],
    );

    /**
     * tmpItemsをfieldsの内容で更新
     */
    React.useEffect(() => {
        const ingredients = getValues('ingredients');

        if (ingredients.length > 0 && categories.length > 0) {
            // 各カテゴリーに属する食材を整理し、必要に応じて空の食材を追加
            const filledItems: IIngredientItem[] = categories
                .map(category => {
                    const itemsInCategory = ingredients.filter(
                        item => item.categoryId === category.id,
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
                                    TMP_ID_PREFIX.INGREDIENT_ITEM,
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
            console.log({ watchFields });
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
                        onDragEnd={handleDragEnd}
                        onDragOver={handleDragOver}>
                        {categories.map(category => {
                            const items = tmpItems.filter(
                                item => item.categoryId === category.id,
                            );
                            const itemsKey = items
                                .map(item => item.id)
                                .join(',');
                            const offsetIndex = watchFields.findIndex(
                                item => item.categoryId === category.id,
                            );
                            return (
                                <IngredientItemList
                                    key={`${category.id}-${itemsKey}`}
                                    control={control}
                                    category={category}
                                    items={items}
                                    offsetIndex={offsetIndex}
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
                                <IngredientEditDialogButton
                                    item={activeItem}
                                    isDisabled={true}
                                    placeholder={`${activeCategory?.name}を設定`}
                                />
                            )}
                        </DragOverlay>
                    </DndContext>
                )}
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
