import { BILLING_PLAN, BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import {
    IBillingStatus,
    PlanActionButtonConfig,
    PlanActionHandlers,
} from '@/types';

import { formatDisplayDate } from './date';

/** 金額を円表示にフォーマットする */
export const formatYen = (amount: number): string =>
    `¥ ${amount.toLocaleString()}`;

/** 買い切りパックの 1 回あたり単価表示 */
export const formatPackUnitPrice = (price: number, credits: number): string =>
    `1回あたり ${formatYen(Math.round(price / credits))}`;

/** プラン選択カードのアクションボタン表示内容を決定する */
export const getPlanActionButtonConfig = (
    plan: (typeof BILLING_PLAN)[keyof typeof BILLING_PLAN],
    billingStatus: IBillingStatus | null,
    handlers: PlanActionHandlers,
): PlanActionButtonConfig | null => {
    if (!billingStatus) {
        return null;
    }

    const pendingPlanChange = billingStatus.pendingPlanChange;
    const isCurrent = billingStatus.plan === plan;
    const isPendingTargetPlan = pendingPlanChange?.nextPlan === plan;

    if (isPendingTargetPlan) {
        return null;
    }

    if (isCurrent) {
        if (pendingPlanChange) {
            return {
                label: '変更を取り消す',
                onClick: () => handlers.onResume(),
                variant: BUTTON_VARIANT.FILLED,
                colorVariant: COLOR_VARIANT.PRIMARY,
                disabled: false,
            };
        } else {
            return null;
        }
    }

    if (plan === BILLING_PLAN.STANDARD) {
        return {
            label: 'このプランを選択',
            onClick: () => handlers.onSubscribe(),
            variant: BUTTON_VARIANT.FILLED,
            colorVariant: COLOR_VARIANT.PRIMARY,
            disabled: false,
        };
    }

    return {
        label: 'ダウングレード',
        onClick: () => handlers.onPortal(),
        variant: BUTTON_VARIANT.OUTLINED,
        colorVariant: COLOR_VARIANT.GRAY,
        disabled: false,
    };
};

/** プラン変更予定バナー：利用可能期間と変更先の説明 */
export const getPendingPlanChangeMessage = (
    currentPlanLabel: string,
    nextPlanLabel: string,
    changesAt: string | null,
): string => {
    const changeDate = changesAt ? formatDisplayDate(changesAt) : null;
    if (changeDate) {
        return `${changeDate} まで${currentPlanLabel}プランをご利用いただけます。その後、${nextPlanLabel}プランへ自動的に切り替わります。`;
    }

    return 'プラン変更予定です（変更日を確認中）';
};

/** 次回請求なし：プラン変更予定時の説明文 */
export const getNoUpcomingInvoicePendingPlanChangeMessage = (
    changesAt: string | null,
): string => {
    const changeDate = changesAt ? formatDisplayDate(changesAt) : null;
    if (changeDate) {
        return `プラン変更予定のため次回請求はありません（${changeDate} まで利用可能）`;
    }

    return 'プラン変更予定のため次回請求はありません（変更日を確認中）';
};
