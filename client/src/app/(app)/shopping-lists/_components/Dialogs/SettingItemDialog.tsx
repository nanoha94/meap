import { Dialog } from '@/app/(app)/_components';
import { Button, FormItem } from '@/components';
import { colors } from '@/constants/colors';
import { useShoppingCategory, useShoppingItem } from '@/hooks/api';
import { IShoppingItem } from '@/types/api';
import { ChevronDown, LoaderCircle } from 'lucide-react';
import React from 'react';
import { useForm, Controller } from 'react-hook-form';

interface Props {
    item?: IShoppingItem | undefined;
    onClose: () => void;
}

interface FormData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

const editMode: { [key: string]: string } = {
    create: '追加',
    update: '編集',
};

type visibleErrorFields = 'name';

const SettingCategoryDialog: React.FC<Props> = ({
    item = undefined,
    onClose,
}) => {
    const { isLoading, createShoppingItem } = useShoppingItem();
    const { shoppingCategories } = useShoppingCategory();

    const type = React.useMemo(
        () => (item !== undefined ? 'update' : 'create'),
        [item],
    );

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
            categoryId: shoppingCategories.find(v => v.isDefault)?.id || '',
        });
    }, [shoppingCategories]);

    const onSubmit = (data: FormData) => {
        createShoppingItem(data);
        onClose();
    };

    return (
        <Dialog title={`買い物アイテムを${editMode[type]}`} onClose={onClose}>
            <div className="w-full flex flex-col items-center gap-y-10">
                {!isLoading ? (
                    <>
                        <form
                            onSubmit={handleSubmit(onSubmit)}
                            className="w-full flex flex-col gap-y-5">
                            <div className="w-full flex flex-col gap-y-5">
                                <div className="flex flex-col gap-y-4">
                                    <FormItem
                                        label="アイテム名/量"
                                        errorMessage={
                                            isErrorVisible.name
                                                ? [errors.name?.message]
                                                : []
                                        }>
                                        <Controller
                                            control={control}
                                            name="name"
                                            rules={{
                                                required: '必須項目です',
                                            }}
                                            render={({
                                                field: { onChange, value },
                                            }) => (
                                                <input
                                                    type="text"
                                                    value={value}
                                                    placeholder="アイテム名と量を入力してください"
                                                    onChange={e => {
                                                        onChange(e);
                                                        setIsErrorVisible(
                                                            prev => ({
                                                                ...prev,
                                                                name: false,
                                                            }),
                                                        );
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
                                            render={({
                                                field: { onChange, value },
                                            }) => (
                                                <div className="relative">
                                                    <select
                                                        value={value}
                                                        onChange={e => {
                                                            onChange(e);
                                                            setIsErrorVisible(
                                                                prev => ({
                                                                    ...prev,
                                                                    name: false,
                                                                }),
                                                            );
                                                        }}
                                                        className="py-2 px-4 w-full text-base border rounded-lg border-gray-main appearance-none outline-none">
                                                        {shoppingCategories.map(
                                                            v => (
                                                                <option
                                                                    key={v.id}
                                                                    value={
                                                                        v.id
                                                                    }>
                                                                    {v.name}
                                                                </option>
                                                            ),
                                                        )}
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
                            </div>
                            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                                <Button
                                    type="button"
                                    colorVariant="gray"
                                    variant="outlined"
                                    onClick={onClose}>
                                    戻る
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={watchName.length <= 0}>
                                    {editMode[type]}
                                </Button>
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
