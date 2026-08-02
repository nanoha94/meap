import type { Metadata } from 'next';

export const METADATA = {
    SITE_NAME: 'meap — レシピと献立をまとめて管理',
    SITE_DESCRIPTION:
        'レシピの保存・整理や献立づくりをサポートするアプリ。写真からAIでレシピを読み込める機能も。まずは無料で始められます。',
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

export const formatPageTitle = (pageTitle: string) =>
    `${pageTitle} | ${METADATA.SITE_NAME}`;

export const buildTitleMetadata = (pageTitle: string): Pick<Metadata, 'title' | 'openGraph'> => {
    const formattedTitle = formatPageTitle(pageTitle);

    return {
        title: { absolute: formattedTitle },
        openGraph: { title: formattedTitle },
    };
};

export const createPageMetadata = (
    title: string,
    description?: string,
): Metadata => {
    const { title: titleMeta, openGraph } = buildTitleMetadata(title);

    return {
        title: titleMeta,
        openGraph: {
            ...openGraph,
            ...(description ? { description } : {}),
        },
        ...(description ? { description } : {}),
    };
};
