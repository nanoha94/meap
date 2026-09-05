'use client';

import React from 'react';
import { useSearchParams } from 'next/navigation';
import { SubmitHandler, useForm } from 'react-hook-form';

import { Button, VerticalRowField } from '@/components';
import { BUTTON_TYPE } from '@/constants';
import { useAuth } from '@/hooks';

interface FormInputs {
    password: string;
    passwordConfirmation: string;
}

type visibleErrorFields = 'password' | 'passwordConfirmation';

const PasswordReset = () => {
    const searchParams = useSearchParams();
    const email = searchParams?.get('email') ?? '';
    const { resetPassword } = useAuth();
    const {
        handleSubmit,
        control,
        formState: { errors },
    } = useForm<FormInputs>({
        defaultValues: {
            password: '',
            passwordConfirmation: '',
        },
    });

    const [apiErrors, setApiErrors] = React.useState<Record<string, string[]>>(
        {},
    );
    const [apiStatus, setApiStatus] = React.useState<string | null>(null);

    // 入力エラーがあったとき、その後に入力内容が変更されればエラー有無に関わらずエラー内容を非表示にする
    const [isErrorVisible, setIsErrorVisible] = React.useState<
        Record<visibleErrorFields, boolean>
    >({
        password: false,
        passwordConfirmation: false,
    });

    /**
     * パスワード再設定フォームの送信
     * @param data フォームの入力値
     */
    const onSubmit: SubmitHandler<FormInputs> = (data: FormInputs) => {
        resetPassword({
            email,
            password: data.password,
            password_confirmation: data.passwordConfirmation,
            setErrors: setApiErrors,
            setStatus: setApiStatus,
        });
    };

    return (
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
                        rules={{
                            required: '必須項目です',
                            minLength: {
                                value: 8,
                                message: '8文字以上で入力してください',
                            },
                        }}>
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
                        rules={{
                            required: '必須項目です',
                            minLength: {
                                value: 8,
                                message: '8文字以上で入力してください',
                            },
                            validate: (value, formValues) => {
                                if (value !== formValues.password) {
                                    return 'パスワードとパスワード（確認用）が一致していません';
                                }
                                return true;
                            },
                        }}>
                        {({ value, onChange, id }) => (
                            <input
                                id={id}
                                type="password"
                                value={value as string}
                                onChange={e => {
                                    onChange(e);
                                    setIsErrorVisible(prev => ({
                                        ...prev,
                                        password_confirmation: false,
                                    }));
                                    setApiErrors({
                                        password_confirmation: [],
                                    });
                                }}
                                className={`py-2 px-4 border rounded-lg ${isErrorVisible.passwordConfirmation && (!!errors.passwordConfirmation?.message || (!!apiErrors.password_confirmation && apiErrors.password_confirmation?.length > 0)) ? 'border-alert-main border-2' : 'border-gray-main'}`}
                            />
                        )}
                    </VerticalRowField>
                </div>
                <div className="flex flex-col gap-y-4">
                    <Button
                        type={BUTTON_TYPE.SUBMIT}
                        onClick={() =>
                            setIsErrorVisible({
                                password: true,
                                passwordConfirmation: true,
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
    );
};

export default PasswordReset;
