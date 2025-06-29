'use client';
import React from 'react';
import { Controller, useForm } from 'react-hook-form';
import { IShoppingItem } from '@/types/api';
import { Button, FormItem } from '@/components/common';
import { ChevronDown } from 'lucide-react';
import { colors } from '@/constants/colors';
import { useShoppingCategories, useShoppingItems } from '../../hooks';

interface Props {
    item?: IShoppingItem | undefined;
    actionButtonText: string;
    onBack: () => void;
}

interface FormData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

type visibleErrorFields = 'name';

const EditForm: React.FC<Props> = ({
    item = undefined,
    actionButtonText,
    onBack,
}) => {
    console.log(item);
    const { createShoppingItem } = useShoppingItems();
    const { storeData } = useShoppingCategories();

    const defaultValues = {
        name: '',
        categoryId: '',
        tags: [],
    };

    const {
        control,
        handleSubmit,
        reset,
        watch,
        formState: { errors },
    } = useForm<FormData>({
        defaultValues,
    });

    const watchName = watch('name');

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({ name: false });

    React.useEffect(() => {
        reset({
            ...defaultValues,
            categoryId: storeData.categories.find(v => v.isDefault)?.id || '',
        });
    }, [storeData.categories]);

    const onSubmit = (data: FormData) => {
        createShoppingItem(data);
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="flex flex-col gap-y-4">
                <FormItem
                    label="アイテム名/量"
                    errorMessage={
                        isErrorVisible.name
                            ? ([errors.name?.message].filter(
                                  Boolean,
                              ) as string[])
                            : []
                    }>
                    <Controller
                        control={control}
                        name="name"
                        rules={{
                            required: '必須項目です',
                        }}
                        render={({ field: { onChange, value } }) => (
                            <input
                                type="text"
                                value={value}
                                placeholder="アイテム名と量を入力してください"
                                onChange={e => {
                                    onChange(e);
                                    setIsErrorVisible(prev => ({
                                        ...prev,
                                        name: false,
                                    }));
                                }}
                                className={`py-2 px-4 text-base border rounded-lg outline-none ${isErrorVisible.name && !!errors.name?.message ? 'border-alert-main' : 'border-gray-main'}`}
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
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            name: false,
                                        }));
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
                    onClick={onBack}>
                    戻る
                </Button>
                <Button type="submit" disabled={watchName.length <= 0}>
                    {actionButtonText}
                </Button>
            </div>
        </form>
    );
};

export default EditForm;
