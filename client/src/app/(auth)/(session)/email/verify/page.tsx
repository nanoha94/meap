'use client';

import React from 'react';
import { useSearchParams } from 'next/navigation';

import { AuthHeading, Button, SnackbarHandler } from '@/components';
import { EMAIL_VERIFY_ERROR_MESSAGES } from '@/constants';
import { useAuth } from '@/hooks';
import { useGlobalStore } from '@/stores';

const EmailVerifyContent = () => {
    // store
    const loadingCount = useGlobalStore(state => state.loadingCount);

    // hook
    const searchParams = useSearchParams();
    const { resendEmailVerification } = useAuth();

    const [message, setMessage] = React.useState<string | null>(null);

    /**
     * メール認証エラー時のメッセージ
     */
    const verifyErrorMessage = React.useMemo(() => {
        const errorCode = searchParams?.get('error');
        if (!errorCode) {
            return null;
        }
        return EMAIL_VERIFY_ERROR_MESSAGES[errorCode] ?? null;
    }, [searchParams]);

    const handleResendEmail = async () => {
        setMessage(null);
        await resendEmailVerification({ setMessage });
    };

    return (
        <>
            {verifyErrorMessage && (
                <SnackbarHandler type="error" message={verifyErrorMessage} />
            )}
            <div className="flex flex-col gap-y-8">
                <AuthHeading>メールアドレス認証</AuthHeading>
                <div className="flex flex-col gap-y-2">
                    <p>
                        ご登録ありがとうございます！アカウントを利用するにはメールアドレスの認証が必要です。
                    </p>
                    <p>
                        ご登録のメールアドレス宛に認証メールを送信しました。メール本文に記載のリンクをクリックして認証を完了してください。
                    </p>
                </div>

                <div className="flex flex-col gap-y-4">
                    <p>
                        メールが届かない場合は以下のボタンをクリックして再送してください。
                    </p>
                    <Button
                        onClick={handleResendEmail}
                        disabled={loadingCount > 0}>
                        認証メールを再送する
                    </Button>
                    {message && <p className="text-alert-main">{message}</p>}
                </div>
            </div>
        </>
    );
};

const Page = () => {
    return (
        <React.Suspense fallback={null}>
            <EmailVerifyContent />
        </React.Suspense>
    );
};

export default Page;
