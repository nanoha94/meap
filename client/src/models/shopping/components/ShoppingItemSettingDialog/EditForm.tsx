'use client';
import React from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Button, FormItem } from '@/components/common';
import { ChevronDown } from 'lucide-react';
import { colors } from '@/constants/colors';
import {
    useShoppingCategories,
    useShoppingItems,
    useShoppingStore,
} from '../../hooks';
import {
    SHOPPING_ITEM_EDIT_MODE,
    SHOPPING_ITEM_SETTING_DIALOG_CONFIGS,
} from '../../constants';

interface Props {
    onClose: () => void;
}

interface FormData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

const EditForm: React.FC<Props> = ({ onClose }) => {
    const { createShoppingItem, updateShoppingItems } = useShoppingItems();
    const { storeData } = useShoppingCategories();
    const { dialogs } = useShoppingStore();
    const { item: editingItem, editMode } = dialogs.itemSetting.payload;

    const defaultValues = {
        name: '',
        categoryId: '',
        tags: [],
    };

    const { control, handleSubmit, reset, watch } = useForm<FormData>({
        defaultValues,
    });

    /**
     * アイテム名の監視
     */
    const watchName = watch('name');

    /**
     * フォームのリセット
     */
    React.useEffect(() => {
        reset({
            ...defaultValues,
            name: editingItem?.name || '',
            categoryId:
                editingItem?.categoryId ||
                storeData.categories.find(v => v.isDefault)?.id,
        });
    }, [storeData.categories]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        if (editMode === SHOPPING_ITEM_EDIT_MODE.CREATE) {
            createShoppingItem(data);
        } else if (editingItem) {
            updateShoppingItems([
                {
                    ...editingItem,
                    ...data,
                },
            ]);
        }
        onClose();
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="flex flex-col gap-y-4">
                <FormItem label="アイテム名/量">
                    <Controller
                        control={control}
                        name="name"
                        render={({ field: { onChange, value } }) => (
                            <input
                                type="text"
                                value={value}
                                placeholder="アイテム名と量を入力してください"
                                onChange={e => {
                                    onChange(e);
                                }}
                                className="py-2 px-4 text-base border rounded-lg outline-none border-gray-main"
                            />
                        )}
                    />
                </FormItem>
                <FormItem label="カテゴリ―">
                    <Controller
                        control={control}
                        name="categoryId"
                        render={({ field: { onChange, value } }) => (
                            <div className="relative">
                                <select
                                    value={value}
                                    onChange={e => {
                                        onChange(e);
                                    }}
                                    className="py-2 px-4 w-full text-base border rounded-lg border-gray-main appearance-none outline-none">
                                    {storeData.categories.map(v => (
                                        <option key={v.id} value={v.id}>
                                            {v.name}
                                        </option>
                                    ))}
                                </select>
                                <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <ChevronDown
                                        size={20}
                                        color={colors.black}
                                    />
                                </div>
                            </div>
                        )}
                    />
                </FormItem>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type="button"
                    colorVariant="gray"
                    variant="outlined"
                    onClick={onClose}>
                    戻る
                </Button>
                <Button type="submit" disabled={watchName.length <= 0}>
                    {SHOPPING_ITEM_SETTING_DIALOG_CONFIGS[editMode].buttonText}
                </Button>
            </div>
        </form>
    );
};

export default EditForm;
