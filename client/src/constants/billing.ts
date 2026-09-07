export const BILLING_PLAN = {
    FREE: 'free',
    STANDARD: 'standard',
} as const;
export type BillingPlan = (typeof BILLING_PLAN)[keyof typeof BILLING_PLAN];

export const BILLING_SUBSCRIPTION_TYPE = {
    STANDARD: 'standard',
} as const;
export type BillingSubscriptionType =
    (typeof BILLING_SUBSCRIPTION_TYPE)[keyof typeof BILLING_SUBSCRIPTION_TYPE];

export const BILLING_PACK_TYPE = {
    LIGHT: 'light',
    VALUE: 'value',
} as const;
export type BillingPackType =
    (typeof BILLING_PACK_TYPE)[keyof typeof BILLING_PACK_TYPE];

export const BILLING_PLAN_LABEL: Record<BillingPlan, string> = {
    [BILLING_PLAN.FREE]: 'フリー',
    [BILLING_PLAN.STANDARD]: 'スタンダード',
};

export const BILLING_PLAN_ORDER: BillingPlan[] = [
    BILLING_PLAN.FREE,
    BILLING_PLAN.STANDARD,
];


export interface BillingPlanDetail {
    plan: BillingPlan;
    label: string;
    /** 月額料金（税込・円）。フリーは 0 */
    price: number;
    /** 月間 AI 利用上限 */
    monthlyCredits: number;
    features: readonly string[];
}

export const BILLING_PLAN_DETAILS: Record<BillingPlan, BillingPlanDetail> = {
    [BILLING_PLAN.FREE]: {
        plan: BILLING_PLAN.FREE,
        label: BILLING_PLAN_LABEL[BILLING_PLAN.FREE],
        price: 0,
        monthlyCredits: 3,
        features: ['レシピ読み込み'],
    },
    [BILLING_PLAN.STANDARD]: {
        plan: BILLING_PLAN.STANDARD,
        label: BILLING_PLAN_LABEL[BILLING_PLAN.STANDARD],
        price: 580,
        monthlyCredits: 30,
        features: [
            'レシピ読み込み',
        ],
    },
};

export const BILLING_CHECKOUT_QUERY = {
    KEY: 'checkout',
    CANCELED: 'canceled',
} as const;

export interface BillingPackDetail {
    type: BillingPackType;
    label: string;
    /** 税込・円 */
    price: number;
    /** 購入で付与される AI 利用回数 */
    credits: number;
    features: readonly string[];
}

export const BILLING_PACK_DETAILS: Record<BillingPackType, BillingPackDetail> = {
    [BILLING_PACK_TYPE.LIGHT]: {
        type: BILLING_PACK_TYPE.LIGHT,
        label: 'ライト',
        price: 400,
        credits: 10,
        features: ['レシピ読み込み'],
    },
    [BILLING_PACK_TYPE.VALUE]: {
        type: BILLING_PACK_TYPE.VALUE,
        label: 'バリュー',
        price: 800,
        credits: 30,
        features: ['レシピ読み込み'],
    },
};

export const BILLING_PACK_ORDER: BillingPackType[] = [
    BILLING_PACK_TYPE.LIGHT,
    BILLING_PACK_TYPE.VALUE,
];

export const BILLING_PACK_OPTIONS: readonly BillingPackDetail[] =
    BILLING_PACK_ORDER.map(type => BILLING_PACK_DETAILS[type]);
