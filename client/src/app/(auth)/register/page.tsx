'use client';

import Button from '@/components/Button';
import Link from 'next/link';
import { useAuth } from '@/hooks/auth';
import React from 'react';
import { Controller, SubmitHandler, useForm } from 'react-hook-form';
import { FormItem } from '@/components';

interface FormInputs {
    name: string;
    email: string;
    password: string;
    passwordConfirmation: string;
}

type visibleErrorFields =
    | 'name'
    | 'email'
    | 'password'
    | 'passwordConfirmation';

const Page = () => {
    const { register } = useAuth({
        middleware: 'guest',
        redirectIfAuthenticated: '/dashboard',
    });

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm<FormInputs>({
        defaultValues: {
            name: '',
            email: '',
            password: '',
            passwordConfirmation: '',
        },
    });

    const [apiErrors, setApiErrors] = React.useState<Record<string, string[]>>(
        {},
    );

    // 入力エラーがあったとき、入力内容の変更でエラーを非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({
        name: false,
        email: false,
        password: false,
        passwordConfirmation: false,
    });

    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        register({
            name: data.name,
            email: data.email,
            password: data.password,
            password_confirmation: data.passwordConfirmation,
            setErrors: setApiErrors,
        });
        setIsErrorVisible({
            name: true,
            email: true,
            password: true,
            passwordConfirmation: true,
        });
    };

    return (
        <>
            <div className="flex flex-col gap-y-10">
                <div className="relative w-full text-center">
                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                    <h1 className="relative w-fit mx-auto px-4 bg-white">
                        アカウント登録
                    </h1>
                </div>
                <p>
                    {/* TODO: リンク設定 */}
                    <Link
                        href="#"
                        className="text-base text-primary-main underline transition-opacity hover:text-opacity-70">
                        利用規約
                    </Link>
                    と{/* TODO: リンク設定 */}
                    <Link
                        href="#"
                        className="text-base text-primary-main underline transition-opacity hover:text-opacity-70">
                        プライバシーポリシー
                    </Link>
                    に同意の上、ご登録ください
                </p>
                <form
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    <div className="flex flex-col gap-y-4">
                        {/* Name */}
                        <FormItem
                            label="ユーザ名"
                            errorMessage={
                                isErrorVisible
                                    ? [
                                          errors.name?.message,
                                          ...(apiErrors?.name || []),
                                      ]
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
                                        onChange={e => {
                                            onChange(e);
                                            setIsErrorVisible(prev => ({
                                                ...prev,
                                                name: false,
                                            }));
                                        }}
                                        className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.name && (errors.name || apiErrors.name) ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>

                        {/* Email Address */}
                        <FormItem
                            label="メールアドレス"
                            errorMessage={
                                isErrorVisible
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
                                        className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.email && (errors.email?.message || apiErrors.email) ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>

                        {/* Password */}
                        <FormItem
                            label="パスワード"
                            errorMessage={
                                isErrorVisible
                                    ? [
                                          errors.password?.message,
                                          ...(apiErrors?.password || []),
                                      ]
                                    : []
                            }>
                            <Controller
                                control={control}
                                name="password"
                                rules={{ required: '必須項目です' }}
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
                                isErrorVisible
                                    ? [
                                          errors.passwordConfirmation?.message,
                                          ...(apiErrors?.passwordConfirmation ||
                                              []),
                                      ]
                                    : []
                            }>
                            <Controller
                                control={control}
                                name="passwordConfirmation"
                                rules={{ required: '必須項目です' }}
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        type="password"
                                        value={value}
                                        onChange={e => {
                                            onChange(e);
                                            setIsErrorVisible(prev => ({
                                                ...prev,
                                                passwordConfirmation: false,
                                            }));
                                        }}
                                        className={`py-2 px-4 text-base border rounded-lg ${isErrorVisible.passwordConfirmation && (errors.passwordConfirmation?.message || apiErrors.passwordConfirmation) ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>
                    </div>
                    <Button>アカウント登録</Button>
                </form>
                <div className="flex flex-col items-center gap-y-4">
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
