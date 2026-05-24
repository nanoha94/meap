'use client';

import React from 'react';
import Link from 'next/link';
import { SubmitHandler, useForm } from 'react-hook-form';

import { Button, ButtonLink, VerticalRowField } from '@/components';
import {
    BUTTON_TYPE,
    BUTTON_VARIANT,
    COLOR_VARIANT,
    LINK_TO,
} from '@/constants';
import { useAuth } from '@/hooks';

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
    const { register } = useAuth();

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

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({
        name: false,
        email: false,
        password: false,
        passwordConfirmation: false,
    });

    /**
     * アカウント登録フォームの送信
     * @param data フォームの入力値
     */
    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        register({
            name: data.name,
            email: data.email,
            password: data.password,
            password_confirmation: data.passwordConfirmation,
            setErrors: setApiErrors,
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
                    <Link
                        href={LINK_TO.TERMS}
                        className="text-primary-main underline transition-opacity hover:text-opacity-70">
                        利用規約
                    </Link>
                    と
                    <Link
                        href={LINK_TO.PRIVACY}
                        className="text-primary-main underline transition-opacity hover:text-opacity-70">
                        プライバシーポリシー
                    </Link>
                    に同意の上、ご登録ください
                </p>
                <form
                    noValidate
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    <div className="flex flex-col gap-y-4">
                        {/* ユーザ名 */}
                        <VerticalRowField
                            control={control}
                            name="name"
                            label="ユーザ名"
                            errorMessage={
                                isErrorVisible.name
                                    ? ([
                                        errors.name?.message,
                                        ...(apiErrors?.name || []),
                                    ].filter(Boolean) as string[])
                                    : []
                            }
                            rules={{
                                required: '必須項目です',
                            }}>
                            {({ value, onChange, id }) => (
                                <input
                                    id={id}
                                    type="text"
                                    value={value as string}
                                    onChange={e => {
                                        onChange(e);
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            name: false,
                                        }));
                                        setApiErrors({ name: [] });
                                    }}
                                    className={`py-2 px-4 border rounded-lg ${isErrorVisible.name && (!!errors.name?.message || (!!apiErrors.name && apiErrors.name?.length > 0)) ? 'border-alert-main border-2' : 'border-gray-main'}`}
                                />
                            )}
                        </VerticalRowField>

                        {/* メールアドレス */}
                        <VerticalRowField
                            control={control}
                            name="email"
                            label="メールアドレス"
                            errorMessage={
                                isErrorVisible.email
                                    ? ([
                                        errors.email?.message,
                                        ...(apiErrors?.email || []),
                                    ].filter(Boolean) as string[])
                                    : []
                            }
                            rules={{
                                required: '必須項目です',
                                pattern: {
                                    value: /^[a-zA-Z0-9_+-]+(.[a-zA-Z0-9_+-]+)*@([a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*\.)+[a-zA-Z]{2,}$/,
                                    message:
                                        'メールアドレスの形式で入力してください',
                                },
                            }}>
                            {({ value, onChange, id }) => (
                                <input
                                    id={id}
                                    type="email"
                                    value={value as string}
                                    onChange={e => {
                                        onChange(e);
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            email: false,
                                        }));
                                        setApiErrors({ email: [] });
                                    }}
                                    className={`py-2 px-4 border rounded-lg ${isErrorVisible.email && (!!errors.email?.message || (!!apiErrors.email && apiErrors.email?.length > 0)) ? 'border-alert-main border-2' : 'border-gray-main'}`}
                                />
                            )}
                        </VerticalRowField>
                        {/* パスワード */}
                        <VerticalRowField
                            control={control}
                            name="password"
                            label="パスワード"
                            memo="8文字以上で、英字・数字・記号をそれぞれ1文字以上含めてください。"
                            errorMessage={
                                isErrorVisible.password
                                    ? ([
                                        errors.password?.message,
                                        ...(apiErrors?.password || []),
                                    ].filter(Boolean) as string[])
                                    : []
                            }
                            rules={{ required: '必須項目です' }}>
                            {({ value, onChange, id }) => (
                                <input
                                    id={id}
                                    type="password"
                                    value={value as string}
                                    onChange={e => {
                                        onChange(e);
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            password: false,
                                        }));
                                        setApiErrors({ password: [] });
                                    }}
                                    className={`py-2 px-4 border rounded-lg ${isErrorVisible.password && (!!errors.password?.message || (!!apiErrors.password && apiErrors.password?.length > 0)) ? 'border-alert-main border-2' : 'border-gray-main'}`}
                                />
                            )}
                        </VerticalRowField>

                        {/* パスワード（確認用） */}
                        <VerticalRowField
                            control={control}
                            name="passwordConfirmation"
                            label="パスワード（確認用）"
                            memo="上記のパスワードと同じ内容を入力してください。"
                            errorMessage={
                                isErrorVisible.passwordConfirmation
                                    ? ([
                                        errors.passwordConfirmation?.message,
                                        ...(apiErrors?.passwordConfirmation ||
                                            []),
                                    ].filter(Boolean) as string[])
                                    : []
                            }
                            rules={{ required: '必須項目です' }}>
                            {({ value, onChange, id }) => (
                                <input
                                    id={id}
                                    type="password"
                                    value={value as string}
                                    onChange={e => {
                                        onChange(e);
                                        setIsErrorVisible(prev => ({
                                            ...prev,
                                            passwordConfirmation: false,
                                        }));
                                        setApiErrors({
                                            passwordConfirmation: [],
                                        });
                                    }}
                                    className={`py-2 px-4 border rounded-lg ${isErrorVisible.passwordConfirmation && (!!errors.passwordConfirmation?.message || (!!apiErrors.passwordConfirmation && apiErrors.passwordConfirmation?.length > 0)) ? 'border-alert-main border-2' : 'border-gray-main'}`}
                                />
                            )}
                        </VerticalRowField>
                    </div>
                    <Button
                        type={BUTTON_TYPE.SUBMIT}
                        onClick={() =>
                            setIsErrorVisible({
                                name: true,
                                email: true,
                                password: true,
                                passwordConfirmation: true,
                            })
                        }>
                        アカウント登録
                    </Button>
                </form>
                <div className="flex flex-col items-center gap-y-4">
                    <Link
                        href={LINK_TO.LOGIN}
                        className="font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
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
                <ButtonLink
                    href={`${(process.env.NEXT_PUBLIC_BACKEND_URL || 'https://localhost:8000').replace(/\/$/, '')}/auth/google/redirect`}
                    variant={BUTTON_VARIANT.OUTLINED}
                    colorVariant={COLOR_VARIANT.GRAY}
                    isExternal={true}
                    openInNewTab={false}
                >
                    Googleアカウントでログイン
                </ButtonLink>
            </div>
        </>
    );
};

export default Page;
