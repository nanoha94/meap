import React from 'react';

import {
    ArrowDown,
    Camera,
    Check,
    ChefHat,
    Link2,
    Sparkles,
} from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

import { DotList, ScreenshotPlaceholder } from './_components';
import { LoginLinks } from '@/components';
import { COLOR_VARIANT, LINK_TO } from '@/constants';
import { getLinkButtonClassName } from '@/utils';

const heroBenefits = [
    'AIで効率的にレシピ登録',
    '登録したレシピから献立を作成',
    '献立から買い物リストへ',
] as const;

const painPoints = [
    '毎日「今日なに作ろう」と考えるのがつらい',
    '買い物のたびに何度もスーパーへ行ってしまう',
    '買った食材を使いきれず、無駄にしてしまう',
] as const;

const flowSteps = [
    {
        step: 1,
        image: '/images/lp/step-01.svg',
        width: 270,
        height: 303,
        title: '数日分の献立をまとめて作る',
        body: '登録したレシピを選ぶだけで、献立表が完成します。（事前にレシピの登録が必要です）',
    },
    {
        step: 2,
        image: '/images/lp/step-02.svg',
        width: 216,
        height: 257,
        title: '食材のまとめ買い',
        body: '献立から食材を選んでリストに追加。数日分の買い物を一回にまとめられます。',
    },
    {
        step: 3,
        image: '/images/lp/step-03.svg',
        width: 428,
        height: 367,
        title: '献立表どおりに調理する',
        body: '事前に作成した献立表に沿って、調理しましょう。作り方を登録しておけば、アプリを見ながら作れます。',
    },
] as const;

const featureShowcaseItems = [
    {
        title: '料理・レシピの登録と一覧',
        body: '材料や手順を登録して、自分だけのレシピ帳を作れます。',
        bullets: [
            '料理名・材料・カテゴリで検索・絞り込み',
            '前回の献立日順で並び替え、しばらく作っていない料理も見つけやすい',
            'タグやカテゴリで自由に分類・整理',
            '写真やURLからAIが自動で読み取り・入力',
        ],
        screenshots: [
            {
                screenName: 'レシピ一覧',
                src: '/images/lp/features-01.jpg',
                imageWidth: 369,
                imageHeight: 665,
            },
            {
                screenName: 'レシピ一覧',
                src: '/images/lp/features-02.jpg',
                imageWidth: 369,
                imageHeight: 665,
            },
        ],
        layout: 'text-first',
    },
    {
        title: '献立表',
        body: '登録済みのレシピを選ぶだけで、かんたんに献立が作れます。',
        bullets: [
            'カレンダーから日付を選んで料理を割り当て',
            '月単位で１ヶ月分の献立を俯瞰',
            '朝・昼・夕の食事をまとめて管理',
        ],
        screenshots: [
            {
                screenName: '献立表',
                src: '/images/lp/features-03.jpg',
                imageWidth: 369,
                imageHeight: 668,
            },
        ],
        layout: 'screenshot-first',
    },
    {
        title: '買い物リスト',
        body: '買うものをカテゴリー別に整理。お店での買い物がスムーズになります。',
        bullets: [
            'カテゴリーを店舗・用途ごとに自由にカスタマイズ',
            '献立の食材をワンタップでまとめて追加',
            '日用品などもテキストで自由に登録',
            'よく買う商品は固定アイテムとして常時表示',
            'チェックしながらお買い物',
        ],
        screenshots: [
            {
                screenName: '買い物リスト',
                src: '/images/lp/features-04.jpg',
                imageWidth: 369,
                imageHeight: 662,
            },
            {
                screenName: '買い物リストに追加',
                src: '/images/lp/features-05.jpg',
                imageWidth: 368,
                imageHeight: 664,
            },
        ],
        layout: 'text-first',
    },
    {
        title: '家族・カップルなど、グループでデータ共有',
        body: '献立・レシピ・買い物リストをグループ内で共有できます。',
        bullets: [
            'QRコードやリンクでかんたんに招待',
            'グループ内で献立作成や買い物を分担',
            '全員のデータがリアルタイムに同期',
        ],
        screenshots: [{ screenName: 'アカウント設定' }],
        layout: 'screenshot-first',
    },
] as const;

const aiComingSoonItems = [
    {
        title: 'AIでレシピ画像を生成',
        body:
            'レシピの内容から、料理のイメージ画像をAIが生成します。見た目の分かりやすいレシピに整えられます。',
    },
    {
        title: 'AIでレシピを提案',
        body:
            '手持ちの食材などの条件をもとに、AIが新しいレシピを提案します。献立のヒントとして活用できます。',
    },
    {
        title: 'AIで献立を提案',
        body:
            '人数や期間などを指定すると、AIが数日分の献立を提案します。毎週の献立づくりをさらにラクにします。',
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
                    className="relative overflow-hidden px-4 pb-14 pt-12 lg:px-6 lg:pb-24 lg:pt-16"
                    aria-labelledby="hero-heading">
                    <div
                        className="pointer-events-none absolute -bottom-40 -right-40 h-[28rem] w-[28rem] rounded-full bg-secondary-light/40 blur-3xl sm:-bottom-28 sm:-right-28"
                        aria-hidden
                    />
                    <div
                        className="pointer-events-none absolute -bottom-20 right-1/3 h-72 w-72 rounded-full bg-primary-light/50 blur-3xl"
                        aria-hidden
                    />
                    <div
                        className="pointer-events-none absolute bottom-0 left-1/4 h-48 w-48 rounded-full bg-secondary-background/80 blur-2xl"
                        aria-hidden
                    />
                    <div className="relative mx-auto flex w-full max-w-5xl justify-center">
                        <div className="flex w-full flex-col items-center gap-10 lg:flex-row lg:items-center lg:gap-8 xl:gap-12">
                            <div className="w-full lg:min-w-0 lg:flex-1">
                                <p className="mb-3 text-lg font-bold text-accent-main">
                                    レシピ・献立・買い物リストを、これひとつで
                                </p>
                                <h1
                                    id="hero-heading"
                                    className="mb-5 text-4xl font-bold leading-tight tracking-wide sm:text-5xl sm:leading-tight">
                                    毎日の献立作りが
                                    <br className="hidden lg:inline" />
                                    ラクになるアプリ
                                </h1>
                                <p className="mb-6 text-lg font-bold leading-loose text-black sm:text-xl">
                                    『今日なに作ろう』に、もう悩まない。
                                </p>
                                <ul className="mb-8 flex list-none flex-wrap gap-2">
                                    {heroBenefits.map((text) => (
                                        <li
                                            key={text}
                                            className="flex items-center gap-2 rounded-full border border-accent-main bg-white px-4 py-2 text-base">
                                            <Check
                                                className="h-4 w-4 shrink-0 text-accent-main"
                                                strokeWidth={4}
                                                aria-hidden
                                            />
                                            {text}
                                        </li>
                                    ))}
                                </ul>
                                <div
                                    id="start"
                                    style={{ scrollMarginTop: '6rem' }}>
                                    <div className="flex flex-wrap items-center gap-3">
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
                                    <p className="mb-0 mt-4 text-base">
                                        登録は無料。AI機能も月3回まで無料で利用できます。
                                    </p>
                                </div>
                            </div>
                            <div className="flex-1 grid w-full min-w-0 grid-cols-2 place-items-center gap-3 sm:flex sm:w-full sm:justify-center sm:gap-5 lg:shrink-0">
                                <ScreenshotPlaceholder
                                    screenName="献立表"
                                    src="/images/lp/hero-01.jpg"
                                    imageWidth={369}
                                    imageHeight={664}
                                    compact
                                    className="w-full min-w-0 sm:w-auto sm:shrink-0"
                                />
                                <ScreenshotPlaceholder
                                    screenName="レシピ一覧"
                                    src="/images/lp/hero-02.jpg"
                                    imageWidth={369}
                                    imageHeight={665}
                                    compact
                                    className="w-full min-w-0 sm:w-auto sm:shrink-0"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    className="bg-white py-14 lg:py-20"
                    aria-labelledby="pain-heading">
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="pain-heading"
                                className="mb-8 text-center text-2xl font-bold text-accent-main lg:mb-10 sm:text-3xl">
                                こんなお悩み、ありませんか？
                            </h2>
                            <div className="grid items-center gap-8 sm:grid-cols-[auto_minmax(0,34rem)] sm:justify-center sm:gap-10 lg:gap-14">
                                <div className="flex justify-center">
                                    <Image
                                        src="/images/lp/worries-01.svg"
                                        alt=""
                                        width={190}
                                        height={319}
                                        className="h-44 w-auto sm:h-64 lg:h-72"
                                        unoptimized
                                    />
                                </div>
                                <ul className="ml-2 list-none space-y-5 sm:ml-0">
                                    {painPoints.map((text) => (
                                        <li
                                            key={text}
                                            className="relative rounded-2xl bg-accent-background px-5 py-4 text-base leading-relaxed sm:text-lg">
                                            {text}
                                            <span
                                                className="absolute -left-[20px] top-1/2 h-0 w-0 -translate-y-1/2 border-y-[7px] border-r-[20px] border-y-transparent border-r-accent-background"
                                                aria-hidden
                                            />
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="mt-10 flex justify-center">
                                <ArrowDown
                                    className="h-8 w-8 text-black"
                                    strokeWidth={3}
                                    aria-hidden
                                />
                            </div>
                            <p className="mt-4 text-center text-xl font-bold leading-relaxed sm:text-2xl">
                                meapなら、
                                <span className="text-accent-main">
                                    数日分の献立をまとめて決めるだけ
                                </span>
                                。
                            </p>
                            <p className="mt-3 text-center text-base leading-relaxed">
                                買い物はまとめて1回に。食材を余らせず、作るときは献立表を見るだけです。
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    className="bg-primary-background py-14 lg:py-20"
                    aria-labelledby="steps-heading">
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="steps-heading"
                                className="mb-8 text-2xl font-bold text-accent-main lg:mb-10 sm:text-3xl">
                                使い方はシンプル、3 ステップ
                            </h2>
                            <ol className="grid list-none gap-6 sm:grid-cols-3 sm:gap-8">
                                {flowSteps.map(
                                    ({
                                        step,
                                        image,
                                        width,
                                        height,
                                        title,
                                        body,
                                    }) => (
                                        <li
                                            key={step}
                                            className="relative rounded-2xl border border-gray-border bg-white p-6 shadow-card sm:p-7">

                                            <p className="mb-1 text-base font-bold uppercase tracking-wide text-accent-main">
                                                Step {step}
                                            </p>
                                            <p className="mb-2 text-lg font-bold leading-snug">
                                                {title}
                                            </p>
                                            <p className="mb-4 text-base leading-relaxed">
                                                {body}
                                            </p>
                                            <div className="flex justify-center">
                                                <Image
                                                    src={image}
                                                    alt=""
                                                    width={width}
                                                    height={height}
                                                    className="h-32 w-auto sm:h-36"
                                                    unoptimized
                                                />
                                            </div>
                                        </li>
                                    ),
                                )}
                            </ol>
                        </div>
                    </div>
                </section>

                <section
                    id="features"
                    className="bg-white py-20 lg:py-28"
                    aria-labelledby="features-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <p className="mb-2 text-base font-bold uppercase tracking-wide text-accent-main">
                                FEATURES
                            </p>
                            <h2
                                id="features-heading"
                                className="mb-3 text-2xl font-bold sm:text-3xl">
                                meapでできること
                            </h2>
                            <p className="mb-10 text-lg leading-relaxed lg:mb-12">
                                レシピの登録から献立表、買い物リストまで。家族やカップルとデータを共有できます。
                            </p>
                            <div className="space-y-20 lg:space-y-28">
                                {featureShowcaseItems.map(
                                    ({
                                        title,
                                        body,
                                        bullets,
                                        layout,
                                        screenshots,
                                    }) => {
                                        const screenshotFirst =
                                            layout === 'screenshot-first';
                                        const multiScreenshot =
                                            screenshots.length > 1;

                                        return (
                                            <div
                                                key={title}
                                                className="flex flex-col gap-8 lg:flex-row lg:justify-center lg:gap-16">
                                                <div className="w-full lg:pt-10 lg:max-w-[500px]">
                                                    <h3 className="mb-3 text-xl font-bold sm:text-2xl">
                                                        {title}
                                                    </h3>
                                                    <p className="mb-4 text-base leading-relaxed">
                                                        {body}
                                                    </p>
                                                    <DotList items={bullets} />
                                                </div>
                                                <div
                                                    className={`flex justify-center lg:shrink-0 ${screenshotFirst ? 'lg:order-first' : ''}`}>
                                                    <div
                                                        className={`w-full min-w-0 place-items-center gap-3 sm:flex sm:gap-5 ${multiScreenshot ? 'grid grid-cols-2' : ''}`}>
                                                        {screenshots.map(
                                                            (
                                                                screenshot,
                                                                index,
                                                            ) => (
                                                                <ScreenshotPlaceholder
                                                                    key={`${title}-${index}`}
                                                                    {...screenshot}
                                                                    compact={
                                                                        multiScreenshot
                                                                    }
                                                                    className={
                                                                        multiScreenshot
                                                                            ? 'w-full min-w-0 sm:w-auto sm:shrink-0'
                                                                            : undefined
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    },
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="ai-features"
                    className="bg-primary-background py-14 lg:py-20"
                    aria-labelledby="ai-features-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <h2
                                id="ai-features-heading"
                                className="mb-3 text-2xl font-bold sm:text-3xl">
                                レシピ登録の手間は、AIにおまかせ。
                            </h2>
                            <p className="mb-10 text-lg leading-relaxed lg:mb-12">
                                画像やURLからレシピを読み込めます。手入力の手間を減らし、レシピ登録をスムーズに。
                            </p>

                            <div className="mb-8 overflow-hidden rounded-2xl border border-gray-border bg-white p-6 shadow-card sm:p-8">

                                <div>
                                    <h3 className="mb-3 text-lg font-bold sm:text-xl">
                                        画像/URLからレシピを読み込み
                                    </h3>
                                    <p className="mb-6 text-base leading-relaxed">
                                        レシピ編集画面の「画像からレシピを読み込む」「URLからレシピを読み込む」ボタンから、画像ファイルを選択するか、レシピページのURLを貼り付けるだけ。AIが内容を読み取り、フォームに自動入力します。
                                    </p>
                                    <div className="grid items-start gap-8 lg:grid-cols-2">
                                        <div>
                                            <ol className="mb-6 list-none space-y-3">
                                                {[
                                                    'レシピメモやWebのスクリーンショットを選択、またはレシピページのURLを貼り付け',
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
                                            <p className="mb-6 text-sm leading-relaxed">
                                                ※ URL読み込みはテキスト形式のレシピページが対象です（動画・画像は非対応）。
                                            </p>
                                        </div>
                                        <div
                                            className="flex items-center justify-center rounded-xl border border-dashed border-gray-border bg-primary-background p-8"
                                            aria-hidden>
                                            <div className="flex items-start gap-3 sm:gap-4">
                                                <div className="flex flex-col items-center gap-2">
                                                    <div className="flex h-14 items-center justify-center gap-2 rounded-xl bg-white px-4 shadow-card">
                                                        <Camera
                                                            className="h-6 w-6 shrink-0 text-primary-main"
                                                            strokeWidth={1.75}
                                                        />
                                                        <span
                                                            className="text-sm"
                                                            aria-hidden>
                                                            /
                                                        </span>
                                                        <Link2
                                                            className="h-6 w-6 shrink-0 text-primary-main"
                                                            strokeWidth={1.75}
                                                        />
                                                    </div>
                                                    <p className="text-center text-sm">
                                                        画像 / URL
                                                    </p>
                                                </div>
                                                <span className="mt-4 text-2xl">
                                                    →
                                                </span>
                                                <div className="flex flex-col items-center gap-2">
                                                    <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-white shadow-card">
                                                        <Sparkles
                                                            className="h-7 w-7 text-accent-main"
                                                            strokeWidth={1.75}
                                                        />
                                                    </div>
                                                    <p className="text-center text-sm">
                                                        AI読み取り
                                                    </p>
                                                </div>
                                                <span className="mt-4 text-2xl">
                                                    →
                                                </span>
                                                <div className="flex flex-col items-center gap-2">
                                                    <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-white shadow-card">
                                                        <ChefHat
                                                            className="h-7 w-7 text-primary-main"
                                                            strokeWidth={1.75}
                                                        />
                                                    </div>
                                                    <p className="text-center text-sm">
                                                        レシピ登録
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <p className="mb-12 text-base leading-relaxed lg:mb-16">
                                AI機能は1回の実行につき利用回数を1回消費します。詳しくは
                                <a
                                    href="#pricing"
                                    className="font-bold text-accent-main underline-offset-2 hover:underline">
                                    料金プラン
                                </a>
                                をご確認ください。
                            </p>

                            <div>
                                <p className="mb-2 text-base font-bold uppercase tracking-wide text-accent-main">
                                    Coming Soon
                                </p>
                                <h3 className="mb-4 text-xl font-bold sm:text-2xl">
                                    今後追加予定のAI機能
                                </h3>
                                <p className="mb-6 text-base leading-relaxed">
                                    今後開発を予定しているAI機能です。内容は予告なく変更になる可能性があります。
                                </p>
                                <ul className="grid gap-6 sm:grid-cols-3 sm:gap-8">
                                    {aiComingSoonItems.map(
                                        ({ title, body }) => (
                                            <li
                                                key={title}
                                                className="rounded-2xl border border-gray-border bg-white p-6 shadow-card sm:p-7">
                                                <h4 className="mb-2 text-lg font-bold sm:text-xl">
                                                    {title}
                                                </h4>
                                                <p className="text-base">
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
                    className="bg-white py-14 lg:py-20"
                    aria-labelledby="pricing-heading"
                    style={{ scrollMarginTop: '4rem' }}>
                    <div className="flex justify-center px-4 sm:px-6">
                        <div className="w-full max-w-5xl">
                            <p className="mb-2 text-base font-bold uppercase tracking-wide text-accent-main">
                                PRICING
                            </p>
                            <h2
                                id="pricing-heading"
                                className="mb-3 text-2xl font-bold sm:text-3xl">
                                まずは月3回、無料でお試し
                            </h2>
                            <p className="mb-10 text-lg leading-relaxed lg:mb-12">
                                AI機能はグループ単位で利用回数を管理します。<br />1人がプランに加入すれば、グループ内の全員がAI機能を使えます。
                            </p>

                            <ul className="mb-8 grid gap-6 sm:grid-cols-2 sm:gap-8 lg:mb-10">
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
                                            className={`rounded-2xl border p-6 shadow-card sm:p-7 bg-primary-background ${highlight
                                                ? 'border-accent-main'
                                                : 'border-gray-border'
                                                }`}>
                                            <div className="mb-1 flex flex-wrap items-center gap-2">
                                                <h3 className="text-lg font-bold sm:text-xl">
                                                    {name}
                                                </h3>
                                                {highlight && (
                                                    <span className="rounded-full bg-accent-main px-2.5 py-0.5 text-xs font-bold text-white">
                                                        おすすめ
                                                    </span>
                                                )}
                                            </div>
                                            <p className="mb-4">
                                                <span className="pr-2 text-3xl font-bold">
                                                    {price}
                                                </span>
                                                {priceNote && (
                                                    <span className="text-base">
                                                        {priceNote}
                                                    </span>
                                                )}
                                            </p>
                                            <DotList items={features} />
                                        </li>
                                    ),
                                )}
                            </ul>

                            <div className="rounded-2xl border border-gray-border bg-primary-background p-6 shadow-card sm:p-7">
                                <h3 className="mb-2 text-lg font-bold sm:text-xl">
                                    追加パック（都度購入）
                                </h3>
                                <p className="mb-4 text-base leading-relaxed">
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
                                            <span className="mx-2">
                                                |
                                            </span>
                                            <span className="font-bold text-accent-main">
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
                    className="flex justify-center bg-accent-background px-4 py-14 sm:px-6 lg:py-20"
                    aria-labelledby="cta-heading">
                    <div className="w-full max-w-5xl text-center">
                        <h2
                            id="cta-heading"
                            className="mb-2 text-xl font-bold text-black sm:text-2xl">
                            今日の献立づくりから、はじめてみませんか？
                        </h2>
                        <p className="mb-6 text-base sm:text-lg">
                            登録は無料。メールアドレスだけで、すぐに使いはじめられます。
                        </p>
                        <Link
                            href={LINK_TO.REGISTER}
                            className={`${getLinkButtonClassName(
                                COLOR_VARIANT.PRIMARY,
                            )} mx-auto`}>
                            アカウントを作成
                        </Link>
                    </div>
                </section>
            </main>

            <footer
                style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }}
                className="bg-white py-6">
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="flex w-full max-w-5xl flex-col items-center gap-3 sm:flex-row sm:justify-center sm:gap-6">
                        <nav
                            aria-label="フッターナビゲーション"
                            className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm">
                            <Link
                                href={LINK_TO.TERMS}
                                className="text-primary-main underline transition-opacity hover:text-opacity-70">
                                利用規約
                            </Link>
                            <Link
                                href={LINK_TO.PRIVACY}
                                className="text-primary-main underline transition-opacity hover:text-opacity-70">
                                プライバシーポリシー
                            </Link>
                        </nav>
                        <p className="text-sm text-gray-main">
                            © {new Date().getFullYear()} meap
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default Home;

