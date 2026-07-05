import React from 'react';
import {
    ArrowDown,
    ArrowUp,
    CircleHelp,
    Coins,
    ChevronDown,
    X,
} from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

import { LoginLinks } from '@/components';
import { LINK_TO } from '@/constants';

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
                        <p className="mb-2 text-sm font-bold text-secondary-main">
                            ヘルプ
                        </p>
                        <h1 className="mb-4 text-2xl font-bold sm:text-3xl">
                            プラン変更の仕組み
                        </h1>
                        <p className="leading-relaxed">
                            プランを変更（アップグレード・ダウングレード・解約）する場合、
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
                            具体例：1/1 にスタンダードプラン（¥580/月）を契約 → 1/10 にプロプランへアップグレード
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '1/1',
                                    title: 'スタンダードプランを契約',
                                    description:
                                        'スタンダードプラン（¥580/月）で利用開始。',
                                    detail: <BillingAmount amount="¥580" />,
                                },
                                {
                                    date: '1/10',
                                    title: 'プロプランへアップグレード（即時反映）',
                                    description:
                                        'プロプランの全機能がすぐに利用できます。\nプロプランの月額から、スタンダードプランの残り日数分を日割りで差し引いた差額が請求されます。',
                                    detail: (
                                        <CalcBreakdown>
                                            <CalcLine label="プロプランの月額（1か月分）" amount="+ ¥980" />
                                            <CalcLine
                                                label="スタンダードプラン残り22日分の返金（¥580 × 22/31）"
                                                amount="− ¥412"
                                                muted
                                            />
                                            <div className="my-1.5 border-t border-dashed border-gray-border" />
                                            <CalcLine label="請求額" amount="¥568" bold />
                                        </CalcBreakdown>
                                    ),
                                },
                                {
                                    date: '2/10',
                                    title: '次回請求',
                                    description:
                                        '以降、毎月10日が請求日になります。',
                                    detail: <BillingAmount amount="¥980" />,
                                },
                            ]}
                        />
                    </Section>

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
                            具体例：1/1 にプロプラン（¥980/月）を契約 → 1/15 にスタンダードプランへダウングレード
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '1/1',
                                    title: 'プロプランを契約',
                                    description:
                                        'プロプラン（¥980/月）で利用開始。',
                                    detail: <BillingAmount amount="¥980" />,
                                },
                                {
                                    date: '1/15',
                                    title: 'スタンダードプランへダウングレード',
                                    description:
                                        '手続き完了。ただし、次の更新日まではプロプランのまま利用できます。',
                                    detail: <BillingAmount amount="なし" />,
                                },
                                {
                                    date: '1/15〜2/1',
                                    title: 'プロプランとして利用を継続',
                                    description:
                                        '次の更新日（2/1）まではプランは切り替わりません。',
                                },
                                {
                                    date: '2/1',
                                    title: '更新日 → スタンダードプランへ自動切り替え',
                                    description:
                                        'スタンダードプランに切り替わります。',
                                    detail: <BillingAmount amount="¥580" />,
                                },
                            ]}
                        />
                    </Section>

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
                            具体例：6/10 にスタンダードプラン（¥580/月）を契約 → 6/20 に解約
                        </ScenarioHeading>
                        <Timeline
                            steps={[
                                {
                                    date: '6/10',
                                    title: 'スタンダードプランを契約',
                                    description:
                                        'スタンダードプラン（¥580/月）で利用開始。',
                                    detail: <BillingAmount amount="¥580" />,
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
                            アップグレード・ダウングレード・解約いずれの場合でもそのまま維持されます。
                        </p>
                    </Section>

                    <Section title="よくある質問" icon={<CircleHelp className="size-6" />}>
                        <FaqList />
                    </Section>

                    <div className="mt-10 rounded-xl border border-secondary-light bg-secondary-background p-5">
                        <p>
                            プラン変更は設定画面の「プラン管理」から行えます。
                            料金の詳細は
                            <Link
                                href={`${LINK_TO.LP}#pricing`}
                                className="mx-1 text-primary-main underline transition-opacity hover:opacity-80">
                                料金ページ
                            </Link>
                            もご確認ください。
                        </p>
                    </div>
                </article>
            </main>

            <footer
                className="bg-white py-8"
                style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }}>
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="w-full text-center text-base text-gray-main">
                        © {new Date().getFullYear()} meap
                    </div>
                </div>
            </footer>
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
                <p className="mb-1 font-bold text-secondary-main">
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
        <span className="font-mono font-bold">{amount}</span>
    </span>
);

const CalcBreakdown = ({ children }: { children: React.ReactNode }) => (
    <div className="mt-3 max-w-lg space-y-1.5 rounded-lg bg-gray-background px-4 py-3">
        {children}
    </div>
);

interface CalcLineProps {
    label: string;
    amount: string;
    muted?: boolean;
    bold?: boolean;
}

const CalcLine = ({ label, amount, muted, bold }: CalcLineProps) => (
    <div className="flex items-baseline justify-between gap-4">
        <span className={muted ? 'text-gray-main' : bold ? 'font-bold' : ''}>
            {label}
        </span>
        <span
            className={`shrink-0 font-mono ${muted ? 'text-gray-main' : bold ? 'text-lg font-bold text-secondary-main' : ''}`}>
            {amount}
        </span>
    </div>
);

/* ── FAQ ── */

const faqItems = [
    {
        question: 'アップグレード後、請求日はいつになりますか？',
        answer: 'アップグレードした日が新しい請求日になります。たとえば 1/10 にアップグレードした場合、次回の請求日は 2/10 になり、以降は毎月10日に請求されます。',
    },
    {
        question: 'プランの契約や変更をした後、やっぱりキャンセルしたくなったら？',
        answer: 'アップグレードは即時反映のためキャンセルできません。その後、ダウングレード・解約することは可能ですが、次の更新日に反映されます。\nダウングレード・解約は、次の更新日の前であればキャンセルして今のプランを継続できます。',
    },
    {
        question: '解約後に再度有料プランに戻れますか？',
        answer: 'はい、いつでも再度加入できます。新規契約として即時反映されます。',
    },
    {
        question: '買い切りパックの残数はプラン変更で消えますか？',
        answer: '消えません。買い切りパックで購入した利用回数には有効期限がなく、プラン変更・解約の影響を一切受けません。',
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
            <div className="flex items-center gap-2 text-secondary-main">{icon}</div>
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
