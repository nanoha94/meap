'use client';

import React from 'react';
import { CircleCheck } from 'lucide-react';
import { useRouter } from 'next/navigation';

import { ButtonLink, Header } from '@/components';
import { LINK_TO } from '@/constants';
// import { useAiUsageApi, useBillingApi } from '@/hooks';

const BillingSuccessPage = () => {
    const router = useRouter();

    return (
        <>
            <Header
                title="購入完了"
                hasBackButton={true}
                onBackClick={() => router.push(LINK_TO.SETTINGS.BILLING)}
            />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto flex flex-col gap-y-6">
                <section className="rounded-lg bg-white p-5 shadow-card flex flex-col items-center gap-y-4 text-center">
                    <CircleCheck
                        size={48}
                        className="text-success-main"
                        strokeWidth={2}
                    />
                    <h2 className="text-xl font-bold">お支払いが完了しました</h2>
                    <p className="text-sm leading-relaxed text-gray-main">
                        ご購入ありがとうございます。プランや利用回数への反映には、数秒かかる場合があります。
                    </p>
                    <ButtonLink href={LINK_TO.SETTINGS.BILLING}>
                        プラン管理へ戻る
                    </ButtonLink>
                </section>
            </main>
        </>
    );
};

export default BillingSuccessPage;
