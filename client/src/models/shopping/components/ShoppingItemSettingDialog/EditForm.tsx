'use client';
import React from 'react';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/common';
import {
    useShoppingCategoryApi,
    useShoppingItemApi,
    useShoppingStore,
} from '../../hooks';
import { SHOPPING_ITEM_SETTING_DIALOG_CONFIGS } from '../../constants';
import StyledSelect from '@/components/common/StyledSelect';
import { VerticalRowField } from '@/components/react-hook-form';
import { EDIT_MODE, DIALOG_NAME, BUTTON_TYPE, COLOR_VARIANT, BUTTON_VARIANT } from '@/constants';

interface Props {
    onClose: () => void;
}

interface FormData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

const EditForm: React.FC<Props> = ({ onClose }) => {
    const { storeShoppingItem, updateShoppingItems } = useShoppingItemApi();
    const { storeData } = useShoppingCategoryApi();
    const { dialogs } = useShoppingStore();
    const { item: editingItem, editMode } =
        dialogs[DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT].payload;

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
        if (editMode === EDIT_MODE.CREATE) {
            storeShoppingItem(data);
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
                <VerticalRowField
                    control={control}
                    name="name"
                    label="アイテム名/量">
                    {({ value, onChange }) => (
                        <input
                            type="text"
                            value={value as string}
                            placeholder="アイテム名と量を入力してください"
                            onChange={e => {
                                onChange(e);
                            }}
                            className="py-2 px-4 border rounded-lg outline-none border-gray-main"
                        />
                    )}
                </VerticalRowField>
                <VerticalRowField
                    control={control}
                    name="categoryId"
                    label="カテゴリー">
                    {({ value, onChange }) => (
                        <StyledSelect
                            value={value as string}
                            name="categoryId"
                            options={storeData.categories}
                            isShowPlaceholder={false}
                            onChange={onChange}
                        />
                    )}
                </VerticalRowField>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON} colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={onClose}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={watchName.length <= 0}>
                    {SHOPPING_ITEM_SETTING_DIALOG_CONFIGS[editMode].buttonText}
                </Button>
            </div>
        </form>
    );
};

export default EditForm;
