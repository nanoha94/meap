import React from 'react';
import { ActionMenu, AlertDialog } from '../../_components';
import { colors } from '@/constants/colors';
import { Check, GripVertical, Pencil, Pin, PinOff, Trash } from 'lucide-react';
import { IGetShoppingItem } from '@/types/api';
import { Button } from '@/components';

interface Props {
    item: Omit<IGetShoppingItem, 'category'>;
    onDelete: () => void;
    onUpdate: (name: string, isPinned: boolean, isChecked: boolean) => void;
}

const ShoppingItem = ({ item, onDelete, onUpdate }: Props) => {
    const { id, name, isPinned = false, isChecked = false } = item;
    const inputRef = React.useRef<HTMLInputElement>(null);
    const [itemName, setItemName] = React.useState<string>(name);
    const [isEditing, setIsEditing] = React.useState<boolean>(name === '');

    const [isOpenDeleteDialog, setIsOpenDeleteDialog] =
        React.useState<boolean>(false);

    const handleClickOutside = (event: MouseEvent) => {
        if (
            inputRef.current &&
            !inputRef.current.contains(event.target as Node)
        ) {
            const currentValue = inputRef.current.value;

            if (currentValue !== itemName) {
                if (currentValue === '') {
                    setIsEditing(false);
                    return;
                }
                setItemName(currentValue);
                onUpdate(currentValue, isPinned, isChecked);
            } else if (currentValue === '') {
                onDelete();
            }
            setIsEditing(false);
        }
    };

    React.useEffect(() => {
        if (isEditing) {
            inputRef.current?.focus();
        }
    }, [isEditing]);

    React.useEffect(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [itemName]);

    return (
        <>
            <div
                className="relative py-3 px-1 w-full text-left flex items-center justify-between rounded bg-white"
                style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}>
                <div className="w-full flex items-center gap-x-4">
                    {isPinned && (
                        <div className="absolute -top-3 -left-3 bg-primary-main rounded-full p-1">
                            <Pin color={colors.white} size={20} />
                        </div>
                    )}
                    <GripVertical color={colors.gray.main} />
                    <div className="flex-1">
                        <input
                            type="checkbox"
                            id={`checkbox-${id}`}
                            checked={isChecked}
                            onChange={() => {
                                onUpdate(itemName, isPinned, !isChecked);
                            }}
                            className="hidden"
                        />
                        <label
                            htmlFor={`checkbox-${id}`}
                            className="relative pl-7 w-full h-full whitespace-nowrap cursor-pointer">
                            <div
                                className={`absolute top-1/2 -translate-y-1/2 left-0 w-5 h-5 rounded border-[1.5px] transition-colors ${
                                    isChecked
                                        ? 'bg-primary-main border-[transparent]'
                                        : 'bg-white border-gray-main'
                                }`}>
                                {isChecked && (
                                    <Check
                                        strokeWidth={3.5}
                                        color={colors.white}
                                        size={20}
                                        className="absolute top-1/2 -translate-y-1/2 left-0"
                                    />
                                )}
                            </div>
                            {!isEditing ? (
                                itemName
                            ) : (
                                <input
                                    ref={inputRef}
                                    type="text"
                                    className="relative focus-visible:outline-none"
                                    defaultValue={itemName}
                                />
                            )}
                        </label>
                    </div>
                    <ActionMenu
                        actionButtons={[
                            {
                                label: '編集する',
                                icon: <Pencil />,
                                onClick: () => setIsEditing(true),
                            },
                            {
                                label: '削除する',
                                icon: <Trash />,
                                onClick: () => setIsOpenDeleteDialog(true),
                            },
                            {
                                label: isPinned ? '固定解除する' : '固定する',
                                icon: isPinned ? <PinOff /> : <Pin />,
                                onClick: () =>
                                    onUpdate(itemName, !isPinned, isChecked),
                            },
                        ]}
                    />
                </div>
            </div>
            {isOpenDeleteDialog && (
                <AlertDialog
                    title="買い物アイテムを削除する"
                    onClose={() => setIsOpenDeleteDialog(false)}>
                    <div className="flex flex-col gap-y-7">
                        <p className="text-center">{name}を削除しますか？</p>
                        <div className="mx-auto max-w-[320px] w-full flex gap-x-3">
                            <Button
                                colorVariant="gray"
                                variant="outlined"
                                onClick={() => setIsOpenDeleteDialog(false)}>
                                キャンセル
                            </Button>
                            <Button
                                onClick={() => onDelete()}
                                colorVariant="alert">
                                削除
                            </Button>
                        </div>
                    </div>
                </AlertDialog>
            )}
        </>
    );
};

export default ShoppingItem;
