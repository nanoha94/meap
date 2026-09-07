export const LEGAL = {
    TRADE_NAME: 'nanoha code',
    SERVICE_NAME: 'meap',
    ADDRESS: '〒980-0021\n宮城県仙台市青葉区中央4丁目8-17 小林ビル1階',
    SUPPORT_EMAIL: 'support@meap.blog',
    OPERATOR_NAME: '阿部 遥菜',
    PHONE_DISCLOSURE:
        'ご請求があった場合に遅滞なく開示いたします。',
} as const;

export const LEGAL_SERVICE_URL =
    process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'https://meap.blog';
