import React from 'react';
import {
    ArrowUp,
    // ArrowDown, // 複数有料プラン追加時: ダウングレードセクションと合わせて有効化
    CircleHelp,
    Coins,
    ChevronDown,
    X,
} from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

import { LoginLinks, Footer } from '@/components';
import {
    BILLING_PLAN,
    BILLING_PLAN_DETAILS,
    LINK_TO,
} from '@/constants';

const STANDARD_MONTHLY = BILLING_PLAN_DETAILS[BILLING_PLAN.STANDARD].price;
const formatYenCompact = (amount: number) => `¥${amount.toLocaleString()}`;
const STANDARD_MONTHLY_LABEL = `${formatYenCompact(STANDARD_MONTHLY)}/月`;

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
                                className="w-auto h-[42px]"
                                loading="eager"
                            />
                        </Link>
                        <LoginLinks />
                    </div>
                </div>
            </header>

            <main className="flex justify-center px-4 py-12">
                <article className="w-full max-w-5xl">
                    <div className="mb-10">
                        <p className="mb-2 text-sm font-bold text-primary-main">
                            ヘルプ
                        </p>
                        <h1 className="mb-4 text-2xl font-bold sm:text-3xl">
                            プラン変更の仕組み
                        </h1>
                        {/* 複数有料プラン追加時: 「アップグレード・ダウングレード・解約」に変更 */}
                        <p className="leading-relaxed">
                            プランを変更（アップグレード・解約）する場合、
                            それぞれ料金の請求タイミングやプランの切り替わり方が異なります。<br />
                            各プラン変更のルールを具体例つきで説明します。
                        </p>
                    </div>

                    <Section
                        title="アップグレード"
                        icon={<ArrowUp className="size-6" />}
                        badge={{ label: '即時反映', color: 'success' }}>
                        <p className="mb-6">
                            上位プランへのアップグレードは<strong>即時反映</strong>されます。
                            請求額は「新プランの月額 − 旧プランの残り日数分（日割り）」で計算されます。
                            次回の請求日もアップグレードした日を基準に更新されます。
                        </p>

                        <ScenarioHeading>
                            具体例：1/1 からフリープランで利用 → 1/10 にスタンダードプラン（{STANDARD_MONTHLY_LABEL}）へアップグレード
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '1/1',
                                    title: 'フリープランで利用開始',
                                    description:
                                        'フリープランで利用開始。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                                {
                                    date: '1/10',
                                    title: 'スタンダードプランへアップグレード（即時反映）',
                                    description:
                                        'スタンダードプランのAI機能がすぐに利用できます。\nフリープランに日割り計算の対象となる料金はないため、スタンダードプランの月額（1か月分）が請求されます。',
                                    detail: (
                                        <BillingAmount amount={formatYenCompact(STANDARD_MONTHLY)} />
                                    ),
                                },
                                {
                                    date: '2/10',
                                    title: '次回請求',
                                    description:
                                        '以降、毎月10日が請求日になります。',
                                    detail: <BillingAmount amount={formatYenCompact(STANDARD_MONTHLY)} />,
                                },
                            ]}
                        />
                    </Section>

                    {/*
                      有料プランがスタンダードのみのため非表示（スタンダード→フリーは解約と同じ）。
                      複数有料プラン追加時: 以下のセクションと ArrowDown import を有効化し、
                      具体例を上位有料プラン→下位有料プラン（例: プロ→スタンダード）に差し替える。
                      冒頭・買い切りパック・FAQ の「ダウングレード」表記も合わせて復元すること。

                    <Section
                        title="ダウングレード"
                        icon={<ArrowDown className="size-6" />}
                        badge={{ label: '次の更新日に反映', color: 'warning' }}>
                        <p className="mb-6">
                            下位プランへのダウングレードは、すぐには反映されません。
                            <strong>次の更新日までは今のプランのまま</strong>利用でき、
                            追加請求や返金は発生しません。
                            更新日になると自動的に新しいプランへ切り替わります。
                        </p>

                        <ScenarioHeading>
                            具体例：1/1 にスタンダードプラン（{STANDARD_MONTHLY_LABEL}）を契約 → 1/15 にフリープランへダウングレード
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '1/1',
                                    title: 'スタンダードプランを契約',
                                    description:
                                        `スタンダードプラン（${STANDARD_MONTHLY_LABEL}）で利用開始。`,
                                    detail: <BillingAmount amount={formatYenCompact(STANDARD_MONTHLY)} />,
                                },
                                {
                                    date: '1/15',
                                    title: 'フリープランへダウングレード',
                                    description:
                                        '手続き完了。ただし、次の更新日まではスタンダードプランのまま利用できます。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                                {
                                    date: '1/15〜2/1',
                                    title: 'スタンダードプランとして利用を継続',
                                    description:
                                        '次の更新日（2/1）まではプランは切り替わりません。',
                                },
                                {
                                    date: '2/1',
                                    title: '更新日 → フリープランへ自動切り替え',
                                    description:
                                        'フリープランに切り替わります。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                            ]}
                        />
                    </Section>
                    */}

                    <Section
                        title="解約"
                        icon={<X className="size-6" />}
                        badge={{ label: '更新日に解約', color: 'neutral' }}>
                        <p className="mb-6">
                            解約しても、<strong>次の更新日までは有料プランをそのまま利用</strong>できます。
                            それまでの間、追加請求は発生しません。
                            更新日を過ぎるとフリープランに切り替わります。
                        </p>

                        <ScenarioHeading>
                            具体例：6/10 にスタンダードプラン（{STANDARD_MONTHLY_LABEL}）を契約 → 6/20 に解約
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '6/10',
                                    title: 'スタンダードプランを契約',
                                    description:
                                        `スタンダードプラン（${STANDARD_MONTHLY_LABEL}）で利用開始。`,
                                    detail: <BillingAmount amount={formatYenCompact(STANDARD_MONTHLY)} />,
                                },
                                {
                                    date: '6/20',
                                    title: '解約を実行',
                                    description:
                                        '手続き完了。ただし、次の更新日まではスタンダードプランのまま利用できます。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                                {
                                    date: '6/20〜7/10',
                                    title: 'スタンダードプランとして利用を継続',
                                    description:
                                        '次の更新日（7/10）まで、スタンダードプランのまま利用できます。',
                                },
                                {
                                    date: '7/10',
                                    title: '更新日 → フリープランへ自動切り替え',
                                    description:
                                        'フリープランに切り替わります。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                            ]}
                        />
                    </Section>

                    <Section title="買い切りパックについて" icon={<Coins className="size-6" />}>
                        <p>
                            買い切りパックで購入した利用回数には<strong>有効期限がなく</strong>、
                            アップグレード・解約いずれの場合でもそのまま維持されます。
                            {/* 複数有料プラン追加時: 「アップグレード・ダウングレード・解約いずれの場合でも」に変更 */}
                        </p>
                    </Section>

                    <Section title="よくある質問" icon={<CircleHelp className="size-6" />}>
                        <FaqList />
                    </Section>

                    <div className="mt-10">
                        <p>
                            プラン変更は設定画面の「プラン管理」から行えます。
                            料金の詳細は、
                            <Link
                                href={`${LINK_TO.LP}#pricing`}
                                className="mx-1 text-primary-main underline transition-opacity hover:opacity-80">
                                料金プラン
                            </Link>
                            もご確認ください。
                        </p>
                    </div>
                </article>
            </main>

            <Footer />
        </div>
    );
};

/* ── タイムライン ── */

interface TimelineStep {
    date: string;
    title: string;
    description: string;
    detail?: React.ReactNode;
}

interface TimelineProps {
    steps: TimelineStep[];
}

const Timeline = ({ steps }: TimelineProps) => (
    <div className="max-w-3xl space-y-4">
        {steps.map((step, i) => (
            <div key={i} className="rounded-xl border bg-white px-5 py-4 shadow-card">
                <p className="mb-1 font-bold text-primary-main">
                    {step.date}
                </p>
                <p className="mb-2 text-lg font-bold">{step.title}</p>
                <p className="leading-relaxed whitespace-pre-wrap">
                    {step.description}
                </p>
                {step.detail}
            </div>
        ))}
    </div>
);

const BillingAmount = ({ amount }: { amount: string }) => (
    <span className="mt-3 inline-flex items-center gap-2.5 rounded-lg bg-gray-background px-4 py-2">
        <span className="text-gray-main">請求額</span>
        <span className="font-bold">{amount}</span>
    </span>
);

/* ── FAQ ── */

const faqItems = [
    {
        question: 'アップグレード後、請求日はいつになりますか？',
        answer: 'アップグレードした日が新しい請求日になります。たとえば 1/10 にアップグレードした場合、次回の請求は 2/10 となり、以降も毎月 10 日に請求されます。',
    },
    {
        question: 'プラン変更や解約の手続きを取り消せますか？',
        answer: 'アップグレードは即時反映のため、取り消して元のプランに戻すことはできません。\n解約予定の取り消しは、次の更新日の前であれば「プラン管理」から行えます。取り消すと有料プランの利用が継続されます。',
        // 複数有料プラン追加時:
        // answer: 'アップグレードは即時反映のため、取り消して元のプランに戻すことはできません。\nダウングレード・解約予定の取り消しは、次の更新日の前であれば「プラン管理」から行えます。取り消すと今のプランの利用が継続されます。',
    },
    {
        question: '解約後に、再度有料プランに加入できますか？',
        answer: 'はい、いつでも再加入できます。加入と同時に有料プランが反映され、請求もその日を基準に始まります。',
    },
    {
        question: '買い切りパックの残数は、アップグレードや解約で消えますか？',
        answer: '消えません。買い切りパックで購入した利用回数に有効期限はなく、アップグレード・解約のいずれにも影響されません。',
    },
] as const;

const FaqList = () => (
    <div className="space-y-4">
        {faqItems.map((item, i) => (
            <details
                key={i}
                className="group rounded-xl border border-gray-border bg-white shadow-card">
                <summary className="cursor-pointer list-none px-5 py-4 font-bold [&::-webkit-details-marker]:hidden">
                    <span className="flex items-center justify-between gap-3">
                        {item.question}
                        <ChevronDown className="size-6 shrink-0 text-gray-main transition-transform group-open:rotate-180" />
                    </span>
                </summary>
                <div className="border-t border-gray-border px-5 py-4 leading-relaxed whitespace-pre-wrap">
                    {item.answer}
                </div>
            </details>
        ))}
    </div>
);

/* ── 共通 UI ── */

interface SectionProps {
    title: string;
    icon: React.ReactNode;
    badge?: { label: string; color: 'success' | 'warning' | 'neutral' };
    children: React.ReactNode;
}

const badgeStyles = {
    success: 'bg-success-background text-success-main',
    warning: 'bg-primary-light text-primary-main',
    neutral: 'bg-gray-light text-gray-main',
} as const;

const Section = ({ title, icon, badge, children }: SectionProps) => (
    <section className="mb-12">
        <div className="mb-5 flex flex-wrap items-center gap-3">
            <div className="flex items-center gap-2 text-primary-main">{icon}</div>
            <h2 className="text-xl font-bold sm:text-2xl">{title}</h2>
            {badge && (
                <span
                    className={`rounded-full px-3 py-1 text-sm font-bold ${badgeStyles[badge.color]}`}>
                    {badge.label}
                </span>
            )}
        </div>
        <div className="[&>*:not(:last-child)]:mb-4 leading-relaxed">{children}</div>
    </section>
);

const ScenarioHeading = ({ children }: { children: React.ReactNode }) => (
    <p className="mb-3 font-bold">▼ {children}</p>
);



export default Page;
