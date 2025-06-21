import {
    Dialog,
    TextButton,
    TextInputAndDelete,
} from '@/app/(app)/_components';
import { Button } from '@/components';
import { colors } from '@/constants/colors';
import { TMP_ID_PREFIX } from '@/constants/ids';
import { useShoppingCategory } from '@/hooks';
import { IPutShoppingCategory } from '@/types/api';
import {
    DndContext,
    DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { CirclePlus, LoaderCircle } from 'lucide-react';
import React from 'react';

interface Props {
    onClose: () => void;
    onSave?: () => void;
}

const CategorySettingDialog: React.FC<Props> = ({ onClose, onSave }) => {
    const { isLoading, shoppingCategories, bulkUpdateShoppingCategories } =
        useShoppingCategory();
    // 入力状態
    const [items, setItems] =
        React.useState<IPutShoppingCategory[]>(shoppingCategories);

    const [addCount, setAddCount] = React.useState<number>(0);

    const addEmptyCategory = () => {
        const emptyItem = items.filter(item => item.name === '');
        if (emptyItem.length > 0) {
            // 空のアイテムがある場合、最初の空アイテムにフォーカスを当てる
            const inputElement = document.querySelector(
                `[data-item-id="${emptyItem[0].id}"] input`,
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }

            return;
        }

        // isDefault=falseの要素の一番下の位置を取得
        const nonDefaultItems = items.filter(item => !item.isDefault);
        const insertIndex =
            nonDefaultItems.length > 0
                ? items.findIndex(
                      item =>
                          item.id ===
                          nonDefaultItems[nonDefaultItems.length - 1].id,
                  ) + 1
                : 0;

        setItems(prev => {
            const newItem = {
                id: `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${addCount.toString()}`,
                name: '',
                isDefault: false,
                order: insertIndex,
            };

            const newItems = [...prev];
            newItems.splice(insertIndex, 0, newItem);

            // orderを再計算
            return newItems.map((item, index) => ({
                ...item,
                order: index,
            }));
        });
        setAddCount(prev => prev + 1);
    };

    const updateItem = (id: string, name: string) => {
        setItems(prev =>
            prev.map(item =>
                item.id === id ? { ...item, name } : { ...item },
            ),
        );
    };

    const deleteItem = (id: string) => {
        setItems(prev => prev.filter(item => item.id !== id));
    };

    const onClickSetting = async () => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = items?.filter(
                v =>
                    (v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY) &&
                        v.name.length > 0) ||
                    !v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY),
            );
            await bulkUpdateShoppingCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            onSave();
        } catch {
            // エラーの場合はダイアログを閉じない
            // エラーハンドリングはcreateOrUpdateShoppingCategoriesで行う
        }
    };

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 5, // 5px ドラッグした時にソート機能を有効にする
            },
        }),
        useSensor(KeyboardSensor),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (active.id !== over.id) {
            setItems(prev => {
                const oldIndex = prev.findIndex(item => item.id === active.id);
                const newIndex = prev.findIndex(item => item.id === over.id);

                return arrayMove(prev, oldIndex, newIndex);
            });
        }
    };

    React.useEffect(() => {
        setItems(shoppingCategories);
    }, [shoppingCategories]);

    return (
        <Dialog title="買い物カテゴリ―設定" onClose={onClose}>
            <div className="w-full flex flex-col items-center gap-y-10">
                {!isLoading ? (
                    <>
                        <div className="w-full flex flex-col gap-y-5">
                            <div className="flex flex-col gap-y-2">
                                <DndContext
                                    onDragEnd={handleDragEnd}
                                    sensors={sensors}>
                                    {!!items && items.length > 0 && (
                                        <SortableContext items={items}>
                                            {items.map(v => (
                                                <InputItem
                                                    key={v.id}
                                                    item={v}
                                                    onUpdate={updateItem}
                                                    onDelete={deleteItem}
                                                />
                                            ))}
                                        </SortableContext>
                                    )}
                                </DndContext>
                            </div>
                            <TextButton
                                onClick={() => {
                                    addEmptyCategory();
                                }}
                                className="!border-none !bg-transparent">
                                <CirclePlus size={20} />
                                追加
                            </TextButton>
                        </div>
                        <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                            <Button
                                colorVariant="gray"
                                variant="outlined"
                                onClick={onClose}>
                                戻る
                            </Button>
                            <Button onClick={onClickSetting}>設定</Button>
                        </div>
                    </>
                ) : (
                    <div className="py-5">
                        <LoaderCircle
                            size={40}
                            color={colors.primary.main}
                            className="animate-spin mx-auto"
                        />
                    </div>
                )}
            </div>
        </Dialog>
    );
};

export default CategorySettingDialog;

interface InputItemProps {
    item: IPutShoppingCategory;
    onUpdate: (id: string, name: string) => void;
    onDelete: (id: string) => void;
}
const InputItem: React.FC<InputItemProps> = ({ item, onUpdate, onDelete }) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            key={item.id}
            ref={setNodeRef}
            style={style}
            data-item-id={item.id}
            {...attributes}
            {...listeners}>
            <TextInputAndDelete
                defaultValue={item.name}
                onUpdate={name => {
                    onUpdate(item.id, name);
                }}
                onDelete={() => {
                    onDelete(item.id);
                }}
                disabledDeleteButton={item.isDefault}
            />
        </div>
    );
};
