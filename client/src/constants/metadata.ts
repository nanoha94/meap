import type { Metadata } from 'next';

export const METADATA = {
    SITE_NAME: 'meap — レシピと献立をまとめて管理',
    SITE_DESCRIPTION:
        'レシピの保存・整理や献立づくりをサポートするアプリ。写真やURLからAIでレシピを読み込める機能も。まずは無料で始められます。',
    OGP_IMAGE: '/ogp.jpg',
    PAGE: {
        RECIPE: '料理・レシピ',
        RECIPE_NEW: 'レシピ作成',
        RECIPE_EDIT: 'レシピ編集',
        RECIPE_DETAIL: 'レシピ',
        PLAN: '献立表',
        PLAN_EDIT: '献立の編集',
        SHOPPING_LIST: '買い物リスト',
        SETTINGS: '設定',
        SETTINGS_ACCOUNT: 'アカウント',
        SETTINGS_BILLING: 'プラン管理',
        BILLING_SUCCESS: '購入完了',
        LOGIN: 'ログイン',
        REGISTER: 'アカウント登録',
        PASSWORD_RESET: 'パスワード再設定',
        EMAIL_VERIFY: 'メールアドレスの確認',
        PRIVACY: 'プライバシーポリシー',
        TERMS: '利用規約',
        HELP_PLAN_CHANGE: 'プラン変更の仕組み',
    },
    PAGE_DESCRIPTION: {
        PRIVACY:
            'meap のプライバシーポリシー。お客様から取得する個人情報の項目、利用目的、第三者提供、安全管理措置、お問い合わせ窓口等について記載しています。',
        TERMS:
            'meap の利用規約。本サービスをご利用いただく際の条件、お客様が登録するコンテンツの取扱い、グループ共有機能、禁止事項、免責事項等について記載しています。',
        HELP_PLAN_CHANGE:
            'meap のプラン変更（アップグレード・ダウングレード・解約）について、料金の請求タイミングと AI 利用回数の変動ルールを具体例とともに説明します。',
    },
} as const;

type PageMetadataOptions = {
    path: string;
    description?: string;
};

const getFrontendBaseUrl = () =>
    process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'http://localhost:3000';

const getPageUrl = (path: string) =>
    new URL(path, getFrontendBaseUrl()).toString();

const formatPageTitle = (pageTitle: string) =>
    `${pageTitle} | ${METADATA.SITE_NAME}`;

/**
 * サイトのOpen Graphメタデータを取得する
 * @param title - ページのタイトル
 * @param description - ページの説明
 * @param path - ページのパス
 * @returns NonNullable<Metadata['openGraph']>
 */
const getSiteOpenGraph = (
    title: string = METADATA.SITE_NAME,
    description: string = METADATA.SITE_DESCRIPTION,
    path?: string,
): NonNullable<Metadata['openGraph']> => ({
    title,
    description,
    images: [{ url: METADATA.OGP_IMAGE, alt: METADATA.SITE_NAME }],
    type: 'website',
    locale: 'ja_JP',
    ...(path ? { url: getPageUrl(path) } : {}),
});

/**
 * サイトのTwitterメタデータを取得する
 * @param title - ページのタイトル
 * @param description - ページの説明
 * @returns NonNullable<Metadata['twitter']>
 */
const getSiteTwitter = (
    title: string = METADATA.SITE_NAME,
    description: string = METADATA.SITE_DESCRIPTION,
): NonNullable<Metadata['twitter']> => ({
    card: 'summary_large_image',
    title,
    description,
    images: [METADATA.OGP_IMAGE],
});

/**
 * ページのメタデータを作成する
 * @param title - ページのタイトル
 * @param options - ページのオプション
 * @param options.path - ページのパス
 * @param options.description - ページの説明
 * @returns Metadata
 */
export const createPageMetadata = (
    title: string,
    options: PageMetadataOptions,
): Metadata => {
    const formattedTitle = formatPageTitle(title);
    const pageDescription = options.description ?? METADATA.SITE_DESCRIPTION;

    return {
        title: { absolute: formattedTitle },
        description: pageDescription,
        openGraph: getSiteOpenGraph(formattedTitle, pageDescription, options.path),
        twitter: getSiteTwitter(formattedTitle, pageDescription),
    };
};

/**
 * 共有・インデックス不要なページ（トークン付き URL 等）向けメタデータ。
 * robots: noindex を付与し、親 layout の OGP / Twitter カードを最小限の値で上書きする。
 */
export const createPrivatePageMetadata = (title: string): Metadata => {
    const formattedTitle = formatPageTitle(title);

    return {
        title: { absolute: formattedTitle },
        robots: { index: false, follow: false },
        openGraph: {
            title: formattedTitle,
            images: [],
        },
        twitter: {
            card: 'summary',
            title: formattedTitle,
            images: [],
        },
    };
};

export const createRootSocialMetadata = (
    path: string,
): Pick<Metadata, 'openGraph' | 'twitter'> => ({
    openGraph: getSiteOpenGraph(
        METADATA.SITE_NAME,
        METADATA.SITE_DESCRIPTION,
        path,
    ),
    twitter: getSiteTwitter(),
});
