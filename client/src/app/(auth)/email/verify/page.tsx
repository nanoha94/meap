'use client';
import { Button } from '@/components/common';
import { useAuth } from '@/hooks/api';
import { useLoadingAnimation } from '@/hooks/useLoadingAnimation';
import { useGlobalStore } from '@/stores';
import React from 'react';

const Page = () => {
    const { resendEmailVerification } = useAuth();
    const { isLoading } = useGlobalStore();
    const [message, setMessage] = React.useState<string | null>(null);
    const [isInitialSent, setIsInitialSent] = React.useState(false);
    const hasInitialSent = React.useRef(false);

    /**
     * 初回のメール送信
     */
    const sendInitialEmail = async () => {
        await resendEmailVerification({
            setMessage: () => {}, // 初回は状態を設定しない
        });
        setIsInitialSent(true);
    };

    React.useEffect(() => {
        if (!hasInitialSent.current) {
            sendInitialEmail();
            hasInitialSent.current = true;
        }
    }, []);

    // 初回送信完了後のみローディングアニメーションを表示
    useLoadingAnimation(isInitialSent);

    // ボタンクリック時の再送（メッセージ表示あり）
    const handleResendEmail = async () => {
        setMessage(null);
        await resendEmailVerification({ setMessage });
    };

    return (
        <div className="flex flex-col gap-y-10">
            <div className="relative w-full text-center">
                <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                <h1 className="relative w-fit mx-auto px-4 bg-white">
                    メールアドレス認証
                </h1>
            </div>
            <div className="flex flex-col gap-y-4">
                <p className="text-xl font-bold">
                    メールアドレス認証をお願いします
                </p>
                <div className="flex flex-col gap-y-2">
                    <p>
                        ご登録ありがとうございます！アカウントを利用するにはメールアドレスの認証が必要です。
                    </p>
                    <p>
                        info@meap.comよりメールを送信しましたので、メール本文に記載のあるリンクをクリックして認証を完了してください。
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-y-4">
                <p>
                    メールが届かない場合は以下のボタンをクリックして再送してください。
                </p>
                <Button
                    onClick={handleResendEmail}
                    disabled={isLoading && isInitialSent}>
                    認証メールを再送する
                </Button>
                {/* TODO: useAuthでスナックバーでメッセージ表示しているので、不要なら削除（要検討） */}
                {message && <p className="text-alert-main">{message}</p>}
            </div>
        </div>
    );
};

export default Page;
