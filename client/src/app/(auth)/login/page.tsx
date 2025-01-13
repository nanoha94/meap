'use client';
import Button from '@/components/Button';
import Link from 'next/link';
import { useAuth } from '@/hooks/auth';
import React, { Suspense, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import AuthSessionStatus from '@/app/(auth)/AuthSessionStatus';
import { FormItem } from '@/components';
import { Controller, SubmitHandler, useForm } from 'react-hook-form';

interface FormInputs {
    email: string;
    password: string;
    isKeepLogin: boolean;
}

const Inner = () => {
    const searchParams = useSearchParams();

    const { login } = useAuth({
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
            password: '',
            isKeepLogin: false,
        },
    });

    const [apiErrors, setApiErrors] = useState<Record<string, string[]>>({});
    const [apiStatus, setApiStatus] = useState<string | null>(null);

    useEffect(() => {
        const resetToken = searchParams.get('reset');
        if (resetToken?.length > 0 && Object.keys(errors).length === 0) {
            setApiStatus(atob(resetToken));
        } else {
            setApiStatus(null);
        }
    }, []);

    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        console.log(data);

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
            <AuthSessionStatus className="mb-4" status={apiStatus} />
            <div className="flex flex-col gap-y-10">
                <div className="relative w-full text-center">
                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                    <h1 className="relative w-fit mx-auto px-4 bg-white">
                        アカウント登録
                    </h1>
                </div>
                <form
                    onSubmit={handleSubmit(onSubmit)}
                    className="flex flex-col gap-y-10">
                    <div className="flex flex-col gap-y-4">
                        {/* Email Address */}
                        <FormItem
                            label="メールアドレス"
                            errorMessage={[
                                errors.email?.message,
                                ...(apiErrors?.email || []),
                            ]}>
                            <Controller
                                control={control}
                                name="email"
                                rules={{ required: '必須項目です' }}
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        value={value}
                                        onChange={onChange}
                                        className={`py-2 px-4 text-base border rounded-lg ${errors.email?.message ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>

                        {/* Password */}
                        <FormItem
                            label="パスワード"
                            errorMessage={[
                                errors.password?.message,
                                ...(apiErrors?.password || []),
                            ]}>
                            <Controller
                                control={control}
                                name="password"
                                rules={{ required: '必須項目です' }}
                                render={({ field: { onChange, value } }) => (
                                    <input
                                        type="password"
                                        value={value}
                                        onChange={onChange}
                                        className={`py-2 px-4 text-base border rounded-lg ${errors.email?.message ? 'border-alert-main' : 'border-gray-main'}`}
                                    />
                                )}
                            />
                        </FormItem>

                        {/* Remember Me */}
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
                                className="cursor-pointer text-base">
                                ログイン状態を保持する
                            </label>
                        </div>
                    </div>
                    <Button>ログイン</Button>
                </form>
                <div className="flex flex-col items-center gap-y-4">
                    <Link
                        href="/register"
                        className="text-base font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        アカウント登録はこちら
                    </Link>
                    <Link
                        href="/password/reset/request"
                        className="text-base font-bold text-primary-main underline transition-opacity hover:text-opacity-70">
                        パスワードをお忘れの方はこちら
                    </Link>
                </div>
            </div>
        </>
    );
};

const Login = () => {
    return (
        <Suspense>
            <Inner />
        </Suspense>
    );
};
export default Login;
