'use client';
import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { Button, StyledSelect, VerticalRowField } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT, EDIT_MODE, EditMode } from '@/constants';
import { useDialog, useNavigationGuard } from '@/hooks';
import {
    useShoppingItemApi,
    useShoppingStore,
} from '@/models/shopping';
import { IShoppingItem } from '@/types';

interface Props {
    editingItem: IShoppingItem | undefined;
    editMode: EditMode;
    defaultCategoryId?: string;
}

interface FormData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

const ShoppingItemEditForm: React.FC<Props> = ({
    editingItem,
    editMode,
    defaultCategoryId,
}) => {
    //store
    const categories = useShoppingStore(state => state.categories);
    const items = useShoppingStore(state => state.items);

    // hook 
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { storeShoppingItems, updateShoppingItems } = useShoppingItemApi();
    const emptyFormDefaults = React.useMemo(
        () => ({
            name: '',
            categoryId: '',
            tags: [] as FormData['tags'],
        }),
        [],
    );
    const { control, handleSubmit, reset } = useForm<FormData>({
        defaultValues: emptyFormDefaults,
    });
    const watchName = useWatch({ control, name: 'name' });
    const watchCategoryId = useWatch({ control, name: 'categoryId' });

    /**
     * 送信ボタンの無効化判定
     * アイテム名が空、または編集内容に変更がない場合は送信ボタンを無効化
     */
    const isDisabledSendButton = React.useMemo(() => {
        if (watchName.length <= 0) return true;
        if (editMode === EDIT_MODE.UPDATE && editingItem) {
            const isSameName = watchName === (editingItem.name || '');
            const resolvedCategoryId =
                editingItem.categoryId ||
                defaultCategoryId ||
                categories.find(v => v.isDefault)?.id;
            const isSameCategory = watchCategoryId === resolvedCategoryId;
            return isSameName && isSameCategory;
        }
        return false;
    }, [watchName, watchCategoryId, editMode, editingItem, categories, defaultCategoryId]);
    useNavigationGuard(!isDisabledSendButton);

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

    /**
     * フォームのリセット
     */
    React.useEffect(() => {
        reset({
            ...emptyFormDefaults,
            name: editingItem?.name || '',
            categoryId:
                editingItem?.categoryId ||
                defaultCategoryId ||
                categories.find(v => v.isDefault)?.id,
        });
    }, [editingItem, categories, defaultCategoryId, reset, emptyFormDefaults]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        if (editMode === EDIT_MODE.CREATE) {
            storeShoppingItems([
                {
                    ...data,
                    order: items.length,
                    isPinned: false,
                    isChecked: false,
                },
            ]);
        } else if (editingItem) {
            updateShoppingItems([
                {
                    ...editingItem,
                    ...data,
                },
            ]);
        }
        closeDialog(false);
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
                    {({ value, onChange, id }) => (
                        <input
                            autoFocus
                            id={id}
                            type="text"
                            value={value as string}
                            placeholder="アイテム名と量を入力してください"
                            onChange={e => {
                                onChange(e);
                            }}
                            className="py-2 px-4 border rounded-lg outline-black border-gray-main"
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
                            options={categories}
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
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>
                    {editMode === EDIT_MODE.CREATE ? '追加' : '保存'}
                </Button>
            </div>
        </form>
    );
};

export default ShoppingItemEditForm;
