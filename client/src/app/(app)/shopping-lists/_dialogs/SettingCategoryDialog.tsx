import { Dialog, TextButton } from '@/app/(app)/_components';
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
import { SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { CirclePlus, GripVertical, LoaderCircle, Trash } from 'lucide-react';
import React from 'react';
import { useForm, useFieldArray, Control, Controller } from 'react-hook-form';

interface Props {
    onClose: () => void;
}

interface FormData {
    categories: IPutShoppingCategory[];
}

const SettingCategoryDialog: React.FC<Props> = ({ onClose }) => {
    const { isLoading, shoppingCategories, bulkUpdateShoppingCategories } =
        useShoppingCategory();

    const { control, handleSubmit, watch, reset } = useForm<FormData>({
        defaultValues: {
            categories: [],
        },
    });

    const { fields, append, remove, move } = useFieldArray({
        control,
        name: 'categories',
    });

    const watchedCategories = watch('categories');

    const addEmptyCategory = () => {
        const emptyItem = watchedCategories.filter(item => item.name === '');

        if (emptyItem.length > 0) {
            // 空のアイテムがある場合、最初の空アイテムにフォーカスを当てる
            const emptyIndex = watchedCategories.findIndex(
                item => item.id === emptyItem[0].id,
            );
            const inputElement = document.querySelector(
                `[data-item-id="${TMP_ID_PREFIX.SHOPPING_CATEGORY}${emptyIndex}"] input`,
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
            return;
        }

        const newItem = {
            id: `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${Date.now()}`,
            name: '',
            isDefault: false,
            order: watchedCategories.length,
        };

        // 末尾に追加
        append(newItem);
    };

    const onSubmit = async (data: FormData) => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
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
            onClose();
        } catch {
            // エラーの場合はダイアログを閉じない
            // エラーハンドリングはbulkUpdateShoppingCategoriesで行う
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

        if (active.id !== over?.id) {
            const oldIndex = parseInt(
                active.id
                    .toString()
                    .replace(TMP_ID_PREFIX.SHOPPING_CATEGORY, ''),
            );
            const newIndex = parseInt(
                over.id.toString().replace(TMP_ID_PREFIX.SHOPPING_CATEGORY, ''),
            );
            move(oldIndex, newIndex);
        }
    };

    React.useEffect(() => {
        // shoppingCategoriesが更新されたらフォームの値を更新
        if (shoppingCategories.length > 0) {
            reset({ categories: shoppingCategories });
        }
    }, [shoppingCategories]);

    return (
        <Dialog title="買い物カテゴリ―設定" onClose={onClose}>
            <div className="w-full flex flex-col items-center gap-y-10">
                {!isLoading ? (
                    <>
                        <form
                            onSubmit={handleSubmit(onSubmit)}
                            className="w-full flex flex-col gap-y-5">
                            <div className="w-full flex flex-col gap-y-5">
                                <div className="flex flex-col gap-y-2">
                                    <DndContext
                                        onDragEnd={handleDragEnd}
                                        sensors={sensors}>
                                        {!!fields && fields.length > 0 && (
                                            <SortableContext
                                                items={fields.map(
                                                    (_, index) =>
                                                        `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`,
                                                )}>
                                                {fields.map((field, index) => (
                                                    <InputItem
                                                        key={field.id}
                                                        index={index}
                                                        control={control}
                                                        onDelete={() =>
                                                            remove(index)
                                                        }
                                                        isDefault={
                                                            field.isDefault
                                                        }
                                                    />
                                                ))}
                                            </SortableContext>
                                        )}
                                    </DndContext>
                                </div>
                                <TextButton
                                    type="button"
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
                                    type="button"
                                    colorVariant="gray"
                                    variant="outlined"
                                    onClick={onClose}>
                                    戻る
                                </Button>
                                <Button type="submit">設定</Button>
                            </div>
                        </form>
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

export default SettingCategoryDialog;

interface InputItemProps {
    index: number;
    control: Control<FormData>;
    onDelete: () => void;
    isDefault: boolean;
}
const InputItem: React.FC<InputItemProps> = ({
    index,
    control,
    onDelete,
    isDefault,
}) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}` });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            data-item-id={`${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`}
            {...attributes}
            {...listeners}>
            {/* <TextInputAndDelete
                control={control}
                name={`categories.${index}.name`}
                onDelete={onDelete}
                disabledDeleteButton={isDefault}
            /> */}
            <div className="flex items-center gap-x-2">
                <GripVertical color={colors.gray.main} />
                <Controller
                    control={control}
                    name={`categories.${index}.name`}
                    render={({ field }) => (
                        <input
                            {...field}
                            type="text"
                            placeholder="カテゴリー名を入力"
                            className="py-2 px-4 flex-1 placeholder:text-gray-main outline-none bg-white rounded-lg border border-gray-main"
                        />
                    )}
                />
                <button
                    type="button"
                    onClick={onDelete}
                    className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default"
                    disabled={isDefault}>
                    <Trash color={colors.primary.main} size={28} />
                </button>
            </div>
        </div>
    );
};
