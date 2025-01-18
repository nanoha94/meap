'use client';
import Button from '@/components/Button';
import { useAuth } from '@/hooks/auth';
import { useState } from 'react';

const Page = () => {
    const { resendEmailVerification } = useAuth({
        middleware: 'auth',
        redirectIfAuthenticated: '/dashboard',
    });

    const [status, setStatus] = useState(null);

    return (
        <>
            <div className="flex flex-col gap-y-10">
                <div className="relative w-full text-center">
                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-px bg-gray-main" />
                    <h1 className="relative w-fit mx-auto px-4 bg-white">
                        仮登録完了
                    </h1>
                </div>
                <div className="flex flex-col gap-y-4">
                    <p className="text-xl font-bold">
                        メールアドレス認証をお願いします
                    </p>
                    <div className="flex flex-col gap-y-2">
                        <p>
                            ご登録ありがとうございます！現在はまだ仮登録の状態です。
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
                        onClick={() => resendEmailVerification({ setStatus })}>
                        認証メールを再送する
                    </Button>
                    {status === 'verification-link-sent' && (
                        <p className="text-alert-main">
                            登録時に入力されたメールアドレス宛にメールアドレス確認リンクを再送しました
                        </p>
                    )}
                </div>
            </div>
        </>
    );
};

export default Page;
