import React from 'react';

import {
    CalendarDays,
    ChefHat,
    Share2,
    ShoppingCart,
    Smartphone,
    Users,
    UtensilsCrossed,
} from 'lucide-react';
import Link from 'next/link';

import { LoginLinks } from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { getLinkButtonClassName } from '@/utils';
import Image from 'next/image';

export const metadata = {
    title: 'meap — レシピと献立をまとめて管理',
    description:
        'レシピの保存・整理や献立づくりをサポートするアプリ。まずは無料で始められます。',
};

const painPoints = [
    '買い物に時間がかかる',
    '食材を無駄にしてしまう',
    '毎日の献立を考えるのがつらい',
] as const;

const flowSteps = [
    {
        step: 1,
        icon: CalendarDays,
        title: '数日分の献立をまとめて作る',
        body: '登録しておいたレシピから選ぶだけです。',
    },
    {
        step: 2,
        icon: ShoppingCart,
        title: '買い物リストを作る',
        body: '献立に必要な食材をリストに載せるだけです。',
    },
    {
        step: 3,
        icon: UtensilsCrossed,
        title: '献立表どおりに調理する',
        body: 'あらかじめ決めたメニューをそのまま作るだけです。',
    },
] as const;

const featureItems = [
    {
        icon: ChefHat,
        title: '料理・レシピの登録と一覧',
        body:
            '材料や手順を登録し、自分だけのレシピ帳が作成できます。料理名・材料名・カテゴリなどで絞り込み表示も可能。',
    },
    {
        icon: CalendarDays,
        title: '献立表（カレンダー表示）',
        body:
            '登録済みの料理データを選んで献立を作成。月単位のカレンダーで献立を俯瞰して確認できます。',
    },
    {
        icon: ShoppingCart,
        title: '買い物リスト',
        body:
            '買うものをカテゴリー別に整理して管理できます。カテゴリーの追加・編集も可能。献立データから必要なものをピックアップして買い物リストに追加することもできます。',
    },
    {
        icon: Users,
        title: '家族・グループでデータ共有',
        body:
            '献立・レシピ・買い物リストなどを家族で共有できます。家族で協力して、献立作成や料理、買い物を分担することもできます。',
    },
] as const;

const roadmapItems = [
    {
        icon: Smartphone,
        title: 'モバイルアプリ版',
        body:
            '外出先でも献立や買い物リストを確認しやすいよう、アプリでの提供を検討しています。時期や対応プラットフォームは未定です。',
    },
    {
        icon: Share2,
        title: 'レシピの外部公開・インポート',
        body:
            'レシピを外部に公開できるようにするとともに、他者が公開したレシピを自分のライブラリに取り込み、再利用できる機能を構想しています。',
    },
] as const;

const Home = () => {
    return (
        <div className="min-h-screen bg-primary-background text-black">
            <header
                className="sticky top-0 bg-white backdrop-blur-sm"
                style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}
            >
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="flex w-full max-w-5xl items-center justify-between gap-4 py-3">
                        <Link
                            href="/"
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

            <main>
                <section
                    className="flex justify-center px-4 pb-16 pt-12 sm:px-6 sm:pb-24 sm:pt-16"
                    aria-labelledby="hero-heading">
                    <div className="w-full max-w-5xl">
                        <p className="mb-3 text-lg font-semibold text-secondary-main">
                            日々の献立づくりをもう少しラクに
                        </p>
                        <h1
                            id="hero-heading"
                            className="mb-5 text-4xl font-bold">
                            レシピと献立を、
                            <br />
                            いつでもサッと引き出せるように。
                        </h1>
                        <p className="mb-8 text-lg text-gray-dark sm:text-xl">
                            meapは、日々の調理や献立づくりを支えるためのアプリです。<br />作成したレシピを探しやすくし、繰り返し使えるようにします。
                        </p>
                        <div
                            id="start"
                            className="flex flex-wrap items-center gap-3"
                            style={{ scrollMarginTop: '6rem' }}>
                            <Link
                                href="/register"
                                className={getLinkButtonClassName(
                                    COLOR_VARIANT.PRIMARY,
                                )}>
                                無料で登録する
                            </Link>
                            <Link
                                href="/login"
                                className={getLinkButtonClassName(
                                    COLOR_VARIANT.GRAY,
                                )}>
                                ログイン
                            </Link>
                        </div>
                    </div>
                </section>

                <section
                    className="border-t border-gray-light bg-white py-14 sm:py-20"
                    aria-labelledby="value-heading">
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="value-heading"
                                className="mb-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                meap で、毎日の料理をもう少しラクに
                            </h2>
                            <p className="mb-10 max-w-2xl text-lg leading-relaxed text-gray-dark">
                                献立づくりから買い物まで、迷いやムダを減らす流れをひとつにまとめます。
                            </p>

                            <div className="mb-12">
                                <h3 className="mb-4 text-lg font-semibold text-secondary-main sm:text-xl">
                                    こんなことで困っていませんか？
                                </h3>
                                <ul className="list-none">
                                    {painPoints.map((text) => (
                                        <li
                                            key={text}
                                            className="flex items-center gap-3 px-4 py-1 text-base leading-relaxed text-black">
                                            <span
                                                className="h-1.5 w-1.5 shrink-0 rounded-full bg-secondary-main"
                                                aria-hidden
                                            />
                                            {text}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <h3 className="mb-4 text-lg font-semibold text-secondary-main sm:text-xl">
                                    使い方はシンプル、3 ステップ
                                </h3>
                                <ol className="grid list-none gap-6 sm:grid-cols-3">
                                    {flowSteps.map(
                                        ({
                                            step,
                                            icon: Icon,
                                            title,
                                            body,
                                        }) => (
                                            <li
                                                key={step}
                                                className="relative rounded-xl border border-gray-border bg-primary-background p-6 shadow-card">
                                                <div className="flex items-start gap-4">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-light text-primary-main">
                                                        <Icon
                                                            className="h-5 w-5"
                                                            strokeWidth={1.75}
                                                            aria-hidden
                                                        />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="mb-1 text-base font-medium uppercase tracking-wide text-gray-main">
                                                            Step {step}
                                                        </p>
                                                        <p className="mb-2 text-lg font-semibold leading-snug">
                                                            {title}
                                                        </p>
                                                        <p className="text-base leading-relaxed text-gray-main">
                                                            {body}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        ),
                                    )}
                                </ol>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="features"
                    className="border-t border-gray-light bg-white py-16 sm:py-20"
                    aria-labelledby="features-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="features-heading"
                                className="mb-2 text-2xl font-bold sm:text-3xl">
                                便利な機能
                            </h2>
                            <p className="mb-10 max-w-2xl text-lg leading-relaxed text-gray-main">
                                ログイン後に使える、meap の主な機能です。献立表・料理/レシピ・買い物リストを行き来しながら、同じアプリ内で献立づくりから買い物のメモまでまとめて扱えます。家族やグループと共有すれば、買い物や調理の役割分担もしやすくなります。
                            </p>
                            <ul className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                                {featureItems.map(
                                    ({ icon: Icon, title, body }) => (
                                        <li
                                            key={title}
                                            className="rounded-xl border border-gray-border bg-primary-background p-6 shadow-card">
                                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-light text-primary-main">
                                                <Icon
                                                    className="h-5 w-5"
                                                    strokeWidth={1.75}
                                                    aria-hidden
                                                />
                                            </div>
                                            <h3 className="mb-2 text-lg font-semibold sm:text-xl">
                                                {title}
                                            </h3>
                                            <p className="text-base leading-relaxed text-gray-main">
                                                {body}
                                            </p>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>
                    </div>
                </section>

                <section
                    id="roadmap"
                    className="border-t border-gray-light bg-accent-background py-16 sm:py-20"
                    aria-labelledby="roadmap-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <p className="mb-2 text-base font-semibold uppercase tracking-wide text-secondary-main">
                                将来の機能（予定）
                            </p>
                            <h2
                                id="roadmap-heading"
                                className="mb-2 text-2xl font-bold sm:text-3xl">
                                今後リリースを目指していること
                            </h2>
                            <p className="mb-10 max-w-2xl text-lg leading-relaxed text-gray-main">
                                開発の進捗や優先順位により内容・時期は変わる可能性があります。<br />詳細が決まり次第、情報を発信していきます。
                            </p>
                            <ul className="grid gap-6 sm:grid-cols-2">
                                {roadmapItems.map(
                                    ({ icon: Icon, title, body }) => (
                                        <li
                                            key={title}
                                            className="rounded-xl border border-dashed border-gray-border bg-white p-6 shadow-card">
                                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-light text-primary-main">
                                                <Icon
                                                    className="h-5 w-5"
                                                    strokeWidth={1.75}
                                                    aria-hidden
                                                />
                                            </div>
                                            <h3 className="mb-2 text-lg font-semibold sm:text-xl">
                                                {title}
                                            </h3>
                                            <p className="text-base leading-relaxed text-gray-main">
                                                {body}
                                            </p>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>
                    </div>
                </section>

                <section
                    className="flex justify-center px-4 py-16 bg-secondary-background sm:px-6 sm:py-20"
                    aria-labelledby="cta-heading">
                    <div className="w-full max-w-5xl text-center">
                        <h2
                            id="cta-heading"
                            className="mb-2 text-xl font-bold text-black sm:text-2xl">
                            まずはアカウントを作成して試してみる
                        </h2>
                        <p className="mb-6 text-base  text-gray-main sm:text-lg">
                            登録後はレシピ一覧など、アプリ内の機能へ進めます。
                        </p>
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center rounded-lg bg-secondary-main px-6 py-3 text-base font-semibold text-white shadow-card transition hover:bg-secondary-main/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-main">
                            アカウントを作成
                        </Link>
                    </div>
                </section>
            </main>

            <footer style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }} className="bg-white py-8">
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="w-full text-center text-base text-gray-main">
                        © {new Date().getFullYear()} meap
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default Home;
