import React from 'react';
import Image from 'next/image';
import Link from 'next/link';

import { LoginLinks, Footer } from '@/components';
import {
    BILLING_PACK_OPTIONS,
    BILLING_PLAN,
    BILLING_PLAN_DETAILS,
    LEGAL,
    LEGAL_SERVICE_URL,
    LINK_TO,
} from '@/constants';

const standardPlan = BILLING_PLAN_DETAILS[BILLING_PLAN.STANDARD];
const packPriceSummary = BILLING_PACK_OPTIONS.map(
    pack => `${pack.label}（${pack.credits}回）：${pack.price}円（税込）`,
).join('、');

const Page = () => {
    return (
        <div className="min-h-dvh bg-primary-background text-black">
            <header
                className="sticky z-30 top-0 bg-white backdrop-blur-sm"
                style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}>
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="flex w-full max-w-5xl items-center justify-between gap-4 py-3">
                        <Link
                            href={LINK_TO.LP}
                            className="flex items-center gap-2 text-primary-main transition-opacity hover:opacity-80">
                            <Image
                                src="/images/meap-logo2.png"
                                alt="meap"
                                width={1224}
                                height={486}
                                className="h-[42px] w-auto"
                                loading="eager"
                            />
                        </Link>
                        <LoginLinks />
                    </div>
                </div>
            </header>

            <main className="flex justify-center px-4 py-12">
                <article className="w-full max-w-5xl">
                    <h1 className="mb-8 text-2xl font-bold sm:text-3xl">
                        特定商取引法に基づく表記
                    </h1>

                    <p className="mb-10 leading-relaxed">
                        {LEGAL.SERVICE_NAME}
                        の有料プランおよび追加パックに関する表示です。
                    </p>

                    <div className="rounded-md bg-white p-4 shadow-card">
                        <dl className="grid grid-cols-1 gap-y-4 sm:grid-cols-[160px_1fr] sm:gap-x-4 sm:gap-y-5">
                            <Row label="事業者名">
                                {LEGAL.TRADE_NAME}
                            </Row>
                            <Row label="代表者">
                                {LEGAL.OPERATOR_NAME}
                            </Row>
                            <Row label="所在地">
                                {LEGAL.ADDRESS}
                            </Row>
                            <Row label="電話番号">
                                {LEGAL.PHONE_DISCLOSURE}
                            </Row>
                            <Row label="メールアドレス">
                                <a
                                    href={`mailto:${LEGAL.SUPPORT_EMAIL}`}
                                    className="text-primary-main underline">
                                    {LEGAL.SUPPORT_EMAIL}
                                </a>
                            </Row>
                            <Row label="サービスURL">
                                <a
                                    href={LEGAL_SERVICE_URL}
                                    className="break-all text-primary-main underline">
                                    {LEGAL_SERVICE_URL}
                                </a>
                            </Row>
                            <Row label="販売価格">
                                <ul className="list-disc pl-5 [&>li:not(:last-child)]:mb-1">
                                    <li>
                                        {standardPlan.label}プラン：月額
                                        {standardPlan.price}円（税込）
                                    </li>
                                    <li>追加パック：{packPriceSummary}</li>
                                </ul>
                            </Row>
                            <Row label="商品代金以外の必要料金">
                                インターネット接続に必要な通信料等は、お客様のご負担となります。
                            </Row>
                            <Row label="支払方法">
                                クレジットカード（Stripe）
                            </Row>
                            <Row label="支払時期">
                                <ul className="list-disc pl-5 [&>li:not(:last-child)]:mb-1">
                                    <li>
                                        有料プラン：お申し込み時、および毎月の契約更新日
                                    </li>
                                    <li>追加パック：お支払い時</li>
                                </ul>
                            </Row>
                            <Row label="商品の引渡時期">
                                決済完了後、直ちに本サービス上でご利用いただけます。
                            </Row>
                            <Row label="返品・キャンセル">
                                <div className="flex flex-col gap-y-3">
                                    <p>
                                        本サービスはインターネット上で提供するデジタルサービスであるため、お支払い後の返品・返金は原則としてお受けできません。
                                    </p>
                                    <p>
                                        有料プランの解約は、本サービス内の「プラン管理」画面から行えます。解約後も次回の更新日までは有料プランをご利用いただけます。詳細は
                                        <Link
                                            href={LINK_TO.HELP.PLAN_CHANGE}
                                            className="text-primary-main underline">
                                            プラン変更の仕組み
                                        </Link>
                                        および
                                        <Link
                                            href={LINK_TO.TERMS}
                                            className="text-primary-main underline">
                                            利用規約
                                        </Link>
                                        第4条をご確認ください。
                                    </p>
                                    <p>
                                        追加パックで付与された利用回数に有効期限はなく、有料プランの変更・解約の影響を受けません。
                                    </p>
                                </div>
                            </Row>
                            <Row label="動作環境">
                                本サービスは、Chrome、Safari、Microsoft
                                Edge 等の最新版ブラウザでの利用を推奨しています。
                            </Row>
                        </dl>
                    </div>

                    <p className="mt-10 text-right text-base text-gray-main">
                        制定日：
                        <span>2026年09月06日</span>
                    </p>
                </article>
            </main>

            <Footer />
        </div>
    );
};

interface RowProps {
    label: string;
    children: React.ReactNode;
}

const Row = ({ label, children }: RowProps) => {
    return (
        <>
            <dt className="font-bold">{label}</dt>
            <dd className="leading-relaxed whitespace-pre-wrap">{children}</dd>
        </>
    );
};

export default Page;
