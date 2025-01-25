'use client';

import Button from '@/components/Button';
import { useAuth } from '@/hooks/auth';
import React from 'react';
import { FormItem } from '@/components';
import { Controller, SubmitHandler, useForm } from 'react-hook-form';
import Link from 'next/link';

interface FormInputs {
    email: string;
}

type visibleErrorFields = 'email';

const Page = () => {
    const { passwordResetRequest } = useAuth({
        middleware: 'guest',
        redirectIfAuthenticated: '/dashboard',
    });

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm<FormInputs>({
        defaultValues: {
            email: '',
        },
    });

    const [apiErrors, setApiErrors] = React.useState<Record<string, string[]>>(
        {},
    );
    const [apiStatus, setApiStatus] = React.useState<string | null>(null);

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({ email: false });

    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        passwordResetRequest({
            email: data.email,
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
                    パスワード再設定のリンクを送信します。
                    <br />
                    ご登録のメールアドレスを入力してください。
                </p>
                <form
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    {/* Email Address */}
                    <FormItem
                        label="メールアドレス"
                        errorMessage={
                            isErrorVisible.email
                                ? [
                                      errors.email?.message,
                                      ...(apiErrors?.email || []),
                                  ]
                                : []
                        }>
                        <Controller
                            control={control}
                            name="email"
                            rules={{
                                required: '必須項目です',
                                pattern: {
                                    value: /^[a-zA-Z0-9_+-]+(.[a-zA-Z0-9_+-]+)*@([a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*\.)+[a-zA-Z]{2,}$/,
                                    message:
                                        'メールアドレスの形式で入力してください',
                                },
                            }}
                            render={({ field: { onChange, value } }) => (
                                <input
                                    type="email"
                                    value={value}
                                    onChange={e => {
                                        onChange(e);
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            email: false,
                                        }));
                                    }}
                                    autoFocus
                                    className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.email && (errors.email?.message || apiErrors.email) ? 'border-alert-main' : 'border-gray-main'}`}
                                />
                            )}
                        />
                    </FormItem>
                    <div className="flex flex-col gap-y-4">
                        <Button
                            type="submit"
                            onClick={() => setIsErrorVisible({ email: true })}>
                            送信
                        </Button>
                        {!!apiStatus && (
                            <p className="text-alert-main">{apiStatus}</p>
                        )}
                    </div>
                </form>
                <div className="flex flex-col items-center gap-y-4">
                    <Link
                        href="/register"
                        className="text-base font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        アカウント登録はこちら
                    </Link>
                    <Link
                        href="/login"
                        className="text-base font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        ログインはこちら
                    </Link>
                </div>
            </div>
            <div className="flex flex-col gap-y-10">
                <div className="relative w-full text-center">
                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                    <h1 className="relative w-fit mx-auto px-4 bg-white">
                        他の方法でログイン
                    </h1>
                </div>
                {/* TODO: リンク？ */}
                <Button type="button" variant="outlined" colorVariant="gray">
                    Googleアカウントでログイン
                </Button>
            </div>
        </>
    );
};

export default Page;
