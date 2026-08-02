import { BillingPlan } from '@/constants';
import { IBaseApiResponseWithData } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// 課金状態取得
export type IGetBillingStatusResponse = IBaseApiResponseWithData<IBillingStatus>;

// 課金サブスクリプション開始
export type IPostBillingSubscripeResponse =
    IBaseApiResponseWithData<IBillingCheckoutData>;

// 課金パック購入
export type IPostBillingPacksResponse =
    IBaseApiResponseWithData<IBillingCheckoutData>;

// 課金ポータル
export type IPostBillingPortalResponse =
    IBaseApiResponseWithData<IBillingPortalData>;

// 請求履歴
export type IGetBillingInvoicesResponse =
    IBaseApiResponseWithData<IBillingInvoices>;

// プラン変更予定取り消し
export type IPostBillingResumeResponse =
    IBaseApiResponseWithData<IBillingStatus>;

//--------------------------------
// データ型
//--------------------------------
export interface IPendingPlanChange {
    nextPlan: BillingPlan;
    changesAt: string;
}

export interface IBillingStatus {
    plan: BillingPlan;
    isSubscribed: boolean;
    subscriptionStatus: string | null;
    subscriptionEndsAt: string | null;
    pendingPlanChange: IPendingPlanChange | null;
    pmType: string | null;
    pmLastFour: string | null;
    pmExpMonth: number | null;
    pmExpYear: number | null;
}

export interface IBillingCheckoutData {
    checkoutUrl: string;
}

export interface IBillingPortalData {
    portalUrl: string;
}

export interface IBillingInvoiceLine {
    description: string;
    quantity: number;
    amount: number;
}

export interface IBillingUpcomingInvoice {
    date: string;
    lines: IBillingInvoiceLine[];
    subtotal: number;
    subtotalExcludingTax: number;
    tax: number;
    total: number;
    amountDue: number;
}

export interface IBillingPastInvoice {
    id: string;
    date: string;
    total: number;
    invoiceUrl: string;
}

export interface IBillingInvoices {
    upcomingInvoice: IBillingUpcomingInvoice | null;
    pastInvoices: IBillingPastInvoice[];
}
