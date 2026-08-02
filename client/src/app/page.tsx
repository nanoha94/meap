import React from 'react';

import {
    CalendarDays,
    Camera,
    ChefHat,
    ImageIcon,
    Lightbulb,
    Share2,
    ShoppingCart,
    Smartphone,
    Sparkles,
    Users,
    UtensilsCrossed,
} from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

import { LoginLinks } from '@/components';
import { COLOR_VARIANT, LINK_TO } from '@/constants';
import { getLinkButtonClassName } from '@/utils';

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

const aiComingSoonItems = [
    {
        icon: ImageIcon,
        title: 'AIでレシピ画像を生成',
        body:
            'レシピの内容から、料理のイメージ画像をAIが生成します。サムネイルがないレシピも、見た目の分かりやすいレシピ帳に整えられます。',
    },
    {
        icon: Lightbulb,
        title: 'AIでレシピを提案',
        body:
            '手持ちの食材や好みの条件をもとに、AIが新しいレシピを提案します。献立に迷ったときのヒントとして活用できます。',
    },
    {
        icon: Sparkles,
        title: 'AIで献立を提案',
        body:
            '人数や期間、好みのジャンルなどを指定すると、AIが数日分の献立をまとめて提案します。毎週の献立づくりをさらにラクにします。',
    },
] as const;

const pricingPlans = [
    {
        name: 'フリー',
        price: '無料',
        priceNote: null,
        highlight: false,
        features: [
            'レシピの作成・管理・共有',
            '買い物リスト・献立',
            'AI機能 月3回まで',
        ],
    },
    {
        name: 'スタンダード',
        price: '480円',
        priceNote: '/ 月',
        highlight: true,
        features: [
            'フリーの全機能',
            'AI機能 月30回まで',
            '今後追加されるAI機能も利用可能',
        ],
    },
] as const;

const addonPacks = [
    { count: 10, price: 200 },
    { count: 30, price: 500 },
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
    {
        icon: ImageIcon,
        title: 'AIでレシピ画像を生成',
        body:
            'レシピの内容からサムネイル用の料理画像をAIが生成する機能を開発中です。',
    },
    {
        icon: Lightbulb,
        title: 'AIでレシピを提案',
        body:
            '手持ちの食材や好みの条件をもとに、AIが新しいレシピを提案する機能を構想しています。',
    },
    {
        icon: Sparkles,
        title: 'AIで献立を提案',
        body:
            '人数や期間を指定すると、AIが数日分の献立をまとめて提案する機能を構想しています。',
    },
] as const;

const Home = () => {
    return (
        <div className="min-h-dvh bg-primary-background text-black">
            <header
                className="sticky z-30 top-0 bg-white backdrop-blur-sm"
                style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}
            >
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

            <main>
                <section
                    className="flex justify-center px-4 pb-16 pt-12 sm:px-6 sm:pb-24 sm:pt-16"
                    aria-labelledby="hero-heading">
                    <div className="w-full max-w-5xl">
                        <p className="mb-3 text-lg font-bold text-secondary-main">
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
                                href={LINK_TO.REGISTER}
                                className={getLinkButtonClassName(
                                    COLOR_VARIANT.PRIMARY,
                                )}>
                                無料で登録する
                            </Link>
                            <Link
                                href={LINK_TO.LOGIN}
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
                                <h3 className="mb-4 text-lg font-bold text-secondary-main sm:text-xl">
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
                                <h3 className="mb-4 text-lg font-bold text-secondary-main sm:text-xl">
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
                                                        <p className="mb-2 text-lg font-bold leading-snug">
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
                            <p className="mb-10 text-lg leading-relaxed text-gray-main">
                                ログイン後に使える、meap の主な機能です。<br />
                                献立表・料理/レシピ・買い物リストを行き来しながら、同じアプリ内で献立づくりから買い物のメモまでまとめて扱えます。<br />
                                家族やグループと共有すれば、買い物や調理の役割分担もしやすくなります。
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
                                            <h3 className="mb-2 text-lg font-bold sm:text-xl">
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
                    id="ai-features"
                    className="border-t border-gray-light bg-accent-background py-16 sm:py-20"
                    aria-labelledby="ai-features-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="ai-features-heading"
                                className="mb-2 text-2xl font-bold sm:text-3xl">
                                AI機能
                            </h2>
                            <p className="mb-10 text-lg leading-relaxed text-gray-main">
                                レシピの登録から献立づくりまで、日々の献立作成・料理をサポートするAI機能を提供しています。<br />手入力の手間を減らすレシピ画像の読み取りや、今後追加予定の画像生成・レシピ提案・献立提案など、料理まわりの作業をAIがお手伝いします。</p>

                            <div className="mb-12 overflow-hidden rounded-xl border border-gray-border bg-white p-6 shadow-card sm:p-8">
                                <div className="grid items-center gap-8 lg:grid-cols-2">
                                    <div>
                                        <h3 className="mb-3 text-lg font-bold sm:text-xl">
                                            画像からレシピを読み込み
                                        </h3>
                                        <p className="mb-4 text-base leading-relaxed text-gray-main">
                                            レシピ編集画面の「画像から読み込み」ボタンから、カメラや画像ファイルを選択するだけ。AIが内容を読み取り、フォームに自動入力します。
                                        </p>
                                        <ol className="list-none space-y-3">
                                            {[
                                                '料理本やWebのスクリーンショットを撮影・選択',
                                                'AIが材料・手順・料理名などを読み取り',
                                                '内容を確認・修正して保存',
                                            ].map((text, index) => (
                                                <li
                                                    key={text}
                                                    className="flex items-start gap-3 text-base leading-relaxed text-black">
                                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-light text-sm font-bold text-primary-main">
                                                        {index + 1}
                                                    </span>
                                                    {text}
                                                </li>
                                            ))}
                                        </ol>
                                    </div>
                                    <div
                                        className="flex items-center justify-center rounded-xl border border-dashed border-gray-border bg-primary-background p-8"
                                        aria-hidden>
                                        <div className="flex items-start gap-4">
                                            <div className="flex flex-col items-center gap-2">
                                                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-white shadow-card">
                                                    <Camera
                                                        className="h-7 w-7 text-primary-main"
                                                        strokeWidth={1.75}
                                                    />
                                                </div>
                                                <p className="text-center text-sm text-gray-main">
                                                    撮影
                                                </p>
                                            </div>
                                            <span className="mt-4 text-2xl text-gray-main">
                                                →
                                            </span>
                                            <div className="flex flex-col items-center gap-2">
                                                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-white shadow-card">
                                                    <Sparkles
                                                        className="h-7 w-7 text-secondary-main"
                                                        strokeWidth={1.75}
                                                    />
                                                </div>
                                                <p className="text-center text-sm text-gray-main">
                                                    AI読み取り
                                                </p>
                                            </div>
                                            <span className="mt-4 text-2xl text-gray-main">
                                                →
                                            </span>
                                            <div className="flex flex-col items-center gap-2">
                                                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-white shadow-card">
                                                    <ChefHat
                                                        className="h-7 w-7 text-primary-main"
                                                        strokeWidth={1.75}
                                                    />
                                                </div>
                                                <p className="text-center text-sm text-gray-main">
                                                    レシピ登録
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p className="mb-1 text-base font-bold uppercase tracking-wide text-secondary-main">
                                    Coming Soon
                                </p>
                                <h3 className="mb-2 text-lg font-bold sm:text-xl">
                                    今後追加予定のAI機能
                                </h3>
                                <p className="mb-6 text-base leading-relaxed text-gray-main">
                                    今後開発を予定しているAI機能です。内容は予告なく変更になる可能性があります。
                                </p>
                                <ul className="grid gap-6 sm:grid-cols-3">
                                    {aiComingSoonItems.map(
                                        ({ icon: Icon, title, body }) => (
                                            <li
                                                key={title}
                                                className="rounded-xl border border-gray-border bg-white p-6 shadow-card">
                                                <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-light text-primary-main">
                                                    <Icon
                                                        className="h-5 w-5"
                                                        strokeWidth={1.75}
                                                        aria-hidden
                                                    />
                                                </div>
                                                <h4 className="mb-2 text-lg font-bold sm:text-xl">
                                                    {title}
                                                </h4>
                                                <p className="text-base leading-relaxed text-gray-main">
                                                    {body}
                                                </p>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="pricing"
                    className="border-t border-gray-light bg-white py-16 sm:py-20"
                    aria-labelledby="pricing-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="pricing-heading"
                                className="mb-2 text-2xl font-bold sm:text-3xl">
                                まずは月3回、無料でお試し
                            </h2>
                            <p className="mb-10 text-lg leading-relaxed text-gray-main">
                                AI機能はグループ（世帯）単位で利用回数を管理します。<br />1人がプランに加入すれば、家族全員がAI機能を使えます。
                            </p>

                            <ul className="mb-10 grid gap-6 sm:grid-cols-2">
                                {pricingPlans.map(
                                    ({
                                        name,
                                        price,
                                        priceNote,
                                        highlight,
                                        features,
                                    }) => (
                                        <li
                                            key={name}
                                            className={`rounded-xl border p-6 shadow-card ${highlight
                                                ? 'border-secondary-main bg-secondary-background'
                                                : 'border-gray-border bg-primary-background'
                                                }`}>
                                            <h3 className="mb-1 text-lg font-bold sm:text-xl">
                                                {name}
                                            </h3>
                                            <p className="mb-4">
                                                <span className="text-3xl font-bold">
                                                    {price}
                                                </span>
                                                {priceNote && (
                                                    <span className="text-base text-gray-main">
                                                        {priceNote}
                                                    </span>
                                                )}
                                            </p>
                                            <ul className="list-none space-y-2">
                                                {features.map((feature) => (
                                                    <li
                                                        key={feature}
                                                        className="flex items-start gap-2 text-base leading-relaxed text-black">
                                                        <span
                                                            className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-secondary-main"
                                                            aria-hidden
                                                        />
                                                        {feature}
                                                    </li>
                                                ))}
                                            </ul>
                                        </li>
                                    ),
                                )}
                            </ul>

                            <div className="rounded-xl border border-gray-border bg-primary-background p-6 shadow-card">
                                <h3 className="mb-2 text-lg font-bold sm:text-xl">
                                    追加パック（都度購入）
                                </h3>
                                <p className="mb-4 text-base leading-relaxed text-gray-main">
                                    月の上限を使い切ったときに、追加でAI機能を購入できます。フリープランの方もご利用いただけます。
                                </p>
                                <ul className="flex flex-wrap gap-4">
                                    {addonPacks.map(({ count, price }) => (
                                        <li
                                            key={count}
                                            className="rounded-lg border border-gray-border bg-white px-5 py-3 text-base">
                                            <span className="font-bold">
                                                {count}回パック
                                            </span>
                                            <span className="mx-2 text-gray-main">
                                                |
                                            </span>
                                            <span className="font-bold text-secondary-main">
                                                {price}円
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
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
                            <p className="mb-2 text-base font-bold uppercase tracking-wide text-secondary-main">
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
                            <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
                                            <h3 className="mb-2 text-lg font-bold sm:text-xl">
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
                            href={LINK_TO.REGISTER}
                            className="inline-flex items-center justify-center rounded-lg bg-secondary-main px-6 py-3 text-base font-bold text-white shadow-card transition hover:bg-secondary-main/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-main">
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
