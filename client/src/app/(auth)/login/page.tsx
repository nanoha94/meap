'use client';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import React from 'react';
import { Controller, SubmitHandler, useForm } from 'react-hook-form';

import { AuthLoading, Button, ButtonLink, VerticalRowField } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { useAuth } from '@/hooks';

interface FormInputs {
    email: string;
    password: string;
    isKeepLogin: boolean;
}

type visibleErrorFields = 'email' | 'password';

const LoginForm = () => {
    const searchParams = useSearchParams();
    const { login } = useAuth();

    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm<FormInputs>({
        defaultValues: {
            email: '',
            password: '',
            isKeepLogin: false,
        },
    });

    const [apiErrors, setApiErrors] = React.useState<Record<string, string[]>>(
        {},
    );
    const [apiStatus, setApiStatus] = React.useState<string | null>(null);

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({ email: false, password: false });

    React.useEffect(() => {
        const resetToken = searchParams?.get('reset');
        if (
            !!resetToken &&
            resetToken?.length > 0 &&
            Object.keys(errors).length === 0
        ) {
            setApiStatus(
                atob(resetToken) === 'Your password has been reset.'
                    ? 'パスワードをリセットしました。'
                    : atob(resetToken),
            );
        } else {
            setApiStatus(null);
        }
    }, [searchParams, errors]);

    /**
     * ログインフォームの送信
     * @param data フォームの入力値
     */
    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        login({
            email: data.email,
            password: data.password,
            remember: data.isKeepLogin,
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
                        ログイン
                    </h1>
                </div>
                <form
                    noValidate
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    <div className="flex flex-col gap-y-4">
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

                        <div className="w-fit flex items-center gap-x-1.5">
                            <Controller
                                control={control}
                                name="isKeepLogin"
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        id="isKeepLogin"
                                        type="checkbox"
                                        checked={value}
                                        onChange={onChange}
                                        className="cursor-pointer w-[18px] h-[18px] border-2 border-gray-main rounded-sm accent-primary-main"
                                    />
                                )}
                            />
                            <label
                                htmlFor="isKeepLogin"
                                className="cursor-pointer">
                                ログイン状態を保持する
                            </label>
                        </div>
                    </div>
                    <div className="flex flex-col gap-y-4">
                        <Button
                            type={BUTTON_TYPE.SUBMIT}
                            onClick={() =>
                                setIsErrorVisible({
                                    email: true,
                                    password: true,
                                })
                            }>
                            ログイン
                        </Button>
                        {!!apiStatus && (
                            <p className="text-alert-main">{apiStatus}</p>
                        )}
                    </div>
                </form>
                <div className="flex flex-col items-center gap-y-4">
                    <Link
                        href="/register"
                        className="font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        アカウント登録はこちら
                    </Link>
                    <Link
                        href="/password/reset/request"
                        className="font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        パスワードをお忘れの方はこちら
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
                >
                    Googleアカウントでログイン
                </ButtonLink>
            </div>
        </>
    );
};

const Login = () => {
    return (
        <React.Suspense fallback={<AuthLoading />}>
            <LoginForm />
        </React.Suspense>
    );
};

export default Login;
