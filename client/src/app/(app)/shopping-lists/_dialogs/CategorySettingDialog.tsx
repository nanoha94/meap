import {
    Dialog,
    TextButton,
    TextInputAndDelete,
} from '@/app/(app)/_components';
import { Button } from '@/components';
import { colors } from '@/constants/colors';
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
    const {
        isLoading,
        shoppingCategories,
        createOrUpdateShoppingCategories,
        deleteShoppingCategory,
    } = useShoppingCategory();
    // const router = useRouter();
    // 入力状態
    const [items, setItems] =
        React.useState<IPutShoppingCategory[]>(shoppingCategories);

    const [addCount, setAddCount] = React.useState<number>(0);

    const addEmptyCategory = () => {
        setItems(prev => [
            ...prev,
            {
                id: addCount.toString(),
                name: '',
                isDefault: false,
                order: prev.length,
            },
        ]);
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
        deleteShoppingCategory(id);
    };

    const onClickSetting = async () => {
        try {
            await createOrUpdateShoppingCategories(items);
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
                                                    id={v.id}
                                                    defaultValue={v?.name}
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
    id: string;
    defaultValue: string;
    onUpdate: (id: string, name: string) => void;
    onDelete: (id: string) => void;
}
const InputItem: React.FC<InputItemProps> = ({
    id,
    defaultValue,
    onUpdate,
    onDelete,
}) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            key={id}
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}>
            <TextInputAndDelete
                defaultValue={defaultValue}
                onUpdate={name => {
                    onUpdate(id, name);
                }}
                onDelete={() => {
                    onDelete(id);
                }}
            />
        </div>
    );
};
