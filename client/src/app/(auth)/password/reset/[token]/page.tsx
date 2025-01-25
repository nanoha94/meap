'use client';

import Button from '@/components/Button';
import { useAuth } from '@/hooks/auth';
import React from 'react';
import { Controller, SubmitHandler, useForm } from 'react-hook-form';
import { FormItem } from '@/components';

interface FormInputs {
    password: string;
    password_confirmation: string;
}

type visibleErrorFields = 'password' | 'password_confirmation';

const PasswordReset = () => {
    const { resetPassword } = useAuth({ middleware: 'guest' });

    const {
        handleSubmit,
        control,
        watch,
        formState: { errors },
    } = useForm<FormInputs>({
        defaultValues: {
            password: '',
            password_confirmation: '',
        },
    });

    const [apiErrors, setApiErrors] = React.useState<Record<string, string[]>>(
        {},
    );
    const [apiStatus, setApiStatus] = React.useState(null);

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({
        password: false,
        password_confirmation: false,
    });

    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        resetPassword({
            password: data.password,
            password_confirmation: data.password_confirmation,
            setErrors: setApiErrors,
            setStatus: setApiStatus,
        });
    };

    return (
        <>
            <div className="flex flex-col gap-y-10">
                <div className="relative w-full text-center">
                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                    <h1 className="relative w-fit mx-auto px-4 bg-white">
                        パスワード再設定
                    </h1>
                </div>
                <p className="text-center">
                    新しく設定するパスワードを入力してください
                </p>
                <form
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    <div className="flex flex-col gap-y-4">
                        {/* Password */}
                        <FormItem
                            label="パスワード"
                            errorMessage={
                                isErrorVisible.password
                                    ? [
                                          errors.password?.message,
                                          ...(apiErrors?.password || []),
                                      ]
                                    : []
                            }>
                            <Controller
                                control={control}
                                name="password"
                                rules={{
                                    required: '必須項目です',
                                    minLength: {
                                        value: 8,
                                        message: '8文字以上で入力してください',
                                    },
                                }}
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        type="password"
                                        value={value}
                                        onChange={e => {
                                            onChange(e);
                                            setIsErrorVisible(prev => ({
                                                ...prev,
                                                password: false,
                                            }));
                                        }}
                                        className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.password && (errors.password?.message || apiErrors.password) ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>

                        {/* Confirm Password */}
                        <FormItem
                            label="パスワード（確認用）"
                            errorMessage={
                                isErrorVisible.password_confirmation
                                    ? [
                                          errors.password_confirmation?.message,
                                          ...(apiErrors?.password_confirmation ||
                                              []),
                                      ]
                                    : []
                            }>
                            <Controller
                                control={control}
                                name="password_confirmation"
                                rules={{
                                    required: '必須項目です',
                                    minLength: {
                                        value: 8,
                                        message: '8文字以上で入力してください',
                                    },
                                    validate: value => {
                                        if (value !== watch('password')) {
                                            return 'パスワードとパスワード（確認用）が一致していません';
                                        }
                                        return true;
                                    },
                                }}
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        type="password"
                                        value={value}
                                        onChange={e => {
                                            onChange(e);
                                            setIsErrorVisible(prev => ({
                                                ...prev,
                                                password_confirmation: false,
                                            }));
                                        }}
                                        className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.password_confirmation && (errors.password_confirmation?.message || apiErrors.password_confirmation) ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>
                    </div>
                    <div className="flex flex-col gap-y-4">
                        <Button
                            type="submit"
                            onClick={() =>
                                setIsErrorVisible({
                                    password: true,
                                    password_confirmation: true,
                                })
                            }>
                            パスワードを再設定する
                        </Button>
                        {!!apiStatus && (
                            <p className="text-alert-main">{apiStatus}</p>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
};

export default PasswordReset;
