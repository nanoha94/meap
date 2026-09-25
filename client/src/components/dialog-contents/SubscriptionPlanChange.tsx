'use client';

import React from 'react';

import { useRouter } from 'next/navigation';

import {
    BillingFeatureList,
    BillingOptionCard,
    Button,
    PendingPlanChangeNote,
    PlanChangeHelpLink,
} from '@/components';
import {
    BILLING_PLAN,
    BILLING_PLAN_DETAILS,
    BILLING_PLAN_LABEL,
    BILLING_PLAN_ORDER,
    BILLING_SUBSCRIPTION_TYPE,
    BillingPlanDetail,
    BUTTON_TYPE,
    COLOR_VARIANT,
} from '@/constants';
import {
    useAlertDialog,
    useBillingApi,
    useDialog,
} from '@/hooks';
import {
    BillingCheckoutOrderRow,
    IBillingStatus,
    PlanActionButtonConfig,
} from '@/types';
import {
    formatYen,
    getPlanActionButtonConfig,
} from '@/utils';
import BillingCheckoutPayment from './BillingCheckoutPayment';

interface Props {
    billingStatus: IBillingStatus | null;
}

const standardPlanDetail = BILLING_PLAN_DETAILS[BILLING_PLAN.STANDARD];
const standardPlanLabel = BILLING_PLAN_LABEL[BILLING_PLAN.STANDARD];

const standardSubscribeOrderRows: BillingCheckoutOrderRow[] = [
    {
        label: 'プラン',
        value: standardPlanLabel,
    },
    {
        label: '月額（税込）',
        value: formatYen(standardPlanDetail.price),
    },
    {
        label: '今回のお支払い',
        value: formatYen(standardPlanDetail.price),
        emphasis: 'total',
    },
];

const SubscriptionPlanChange = ({
    billingStatus,
}: Props) => {
    const router = useRouter();
    const { openAlertDialog } = useAlertDialog();
    const { closeAllDialogs, openDialog } = useDialog();
    const {
        createSubscription,
        cancelSubscription,
        resumeSubscription,
    } = useBillingApi();

    /**
     * プラン変更を完了する
     * @param success プラン変更が成功したかどうか
     */
    const completePlanChange = React.useCallback(
        (success: boolean) => {
            if (success) {
                router.refresh();
                closeAllDialogs();
            }
        },
        [closeAllDialogs, router],
    );

    /**
     * スタンダードプランを購入する
     */
    const handleSubscribe = React.useCallback(() => {
        if (!billingStatus) {
            return;
        }

        openDialog({
            title: 'スタンダードプランのお申し込み',
            children: (
                <BillingCheckoutPayment
                    billingStatus={billingStatus}
                    orderRows={standardSubscribeOrderRows}
                    orderNote="お申し込み後、スタンダードプランが即時反映され、上記金額が請求されます。"
                    submitButtonText="申し込む"
                    onSubmit={cardToken =>
                        createSubscription(
                            BILLING_SUBSCRIPTION_TYPE.STANDARD,
                            cardToken,
                        )
                    }
                    onSuccess={() => {
                        completePlanChange(true);
                    }}
                />
            ),
            maxWidth: 480,
        });
    }, [
        billingStatus,
        completePlanChange,
        createSubscription,
        openDialog,
    ]);

    /**
     * サブスクプランをダウングレードする
     */
    const handleDowngrade = React.useCallback(() => {
        if (!billingStatus) {
            return;
        }

        const currentPlanLabel = BILLING_PLAN_LABEL[billingStatus.plan];
        const freePlanLabel = BILLING_PLAN_LABEL[BILLING_PLAN.FREE];

        openAlertDialog(
            {
                title: `${freePlanLabel}プランへダウングレードしますか？`,
                message: [
                    `次の更新日までは${currentPlanLabel}プランをご利用いただけます。`,
                ],
                alertMessage: '',
                actionButtonText: 'ダウングレード',
            },
            async () => {
                const success = await cancelSubscription();
                completePlanChange(success);
            },
        );
    }, [
        billingStatus,
        cancelSubscription,
        completePlanChange,
        openAlertDialog,
    ]);

    const handleResume = React.useCallback(() => {
        if (!billingStatus?.pendingPlanChange) {
            return;
        }

        const currentPlanLabel = BILLING_PLAN_LABEL[billingStatus.plan];
        openAlertDialog(
            {
                title: 'プラン変更予定を取り消しますか？',
                message: [`${currentPlanLabel}プランのご利用が継続されます。`],
                alertMessage: '',
                actionButtonText: '取り消す',
            },
            async () => {
                const success = await resumeSubscription();
                completePlanChange(success);
            },
        );
    }, [
        billingStatus,
        completePlanChange,
        openAlertDialog,
        resumeSubscription,
    ]);

    return (
        <div className="flex w-full flex-col gap-y-6">
            <div className="flex w-full flex-col gap-y-4 sm:flex-row sm:gap-x-4">
                {BILLING_PLAN_ORDER.map(plan => {
                    const detail = BILLING_PLAN_DETAILS[plan];
                    const isCurrentPlan = billingStatus?.plan === plan;

                    return (
                        <PlanColumn
                            key={plan}
                            detail={detail}
                            billingStatus={billingStatus}
                            isCurrentPlan={isCurrentPlan}
                            actionButtonConfig={getPlanActionButtonConfig(
                                plan,
                                billingStatus,
                                {
                                    onSubscribe: handleSubscribe,
                                    onDowngrade: handleDowngrade,
                                    onResume: handleResume,
                                },
                            )}
                        />
                    );
                })}
            </div>
            <div className="mx-auto w-fit"><PlanChangeHelpLink /></div>
        </div>
    );
};

export default SubscriptionPlanChange;

interface PlanColumnProps {
    detail: BillingPlanDetail;
    billingStatus: IBillingStatus | null;
    isCurrentPlan: boolean;
    actionButtonConfig: PlanActionButtonConfig | null;
}

const PlanColumn = ({
    detail,
    billingStatus,
    isCurrentPlan,
    actionButtonConfig,
}: PlanColumnProps) => {
    const showPendingPlanChangeNote =
        isCurrentPlan && billingStatus?.pendingPlanChange;

    return (
        <BillingOptionCard
            badge={
                isCurrentPlan
                    ? {
                        label: '現在のプラン',
                        colorVariant: COLOR_VARIANT.GRAY,
                    }
                    : undefined
            }
            header={{
                title: detail.label,
                price: `${formatYen(detail.price)} / 月`,
            }}
            footer={
                <>
                    {showPendingPlanChangeNote &&
                        billingStatus?.pendingPlanChange && (
                            <PendingPlanChangeNote
                                currentPlanLabel={detail.label}
                                pendingPlanChange={
                                    billingStatus.pendingPlanChange
                                }
                            />
                        )}
                    {actionButtonConfig && (
                        <Button
                            type={BUTTON_TYPE.BUTTON}
                            variant={actionButtonConfig.variant}
                            colorVariant={actionButtonConfig.colorVariant}
                            disabled={actionButtonConfig.disabled}
                            onClick={actionButtonConfig.onClick}>
                            {actionButtonConfig.label}
                        </Button>
                    )}
                </>
            }
            className={isCurrentPlan ? 'border-gray-main border-2' : undefined}
        >
            <dl className="flex flex-col gap-y-4 text-sm">
                <div>
                    <dt className="mb-1 text-xs font-bold text-gray-main">
                        月間 AI 使用回数
                    </dt>
                    <dd className="text-lg font-bold">
                        {detail.monthlyCredits} 回
                    </dd>
                </div>

                <div>
                    <dt className="mb-1 text-xs font-bold text-gray-main">
                        使用可能な AI 機能
                    </dt>
                    <dd>
                        <BillingFeatureList features={detail.features} />
                    </dd>
                </div>
            </dl>
        </BillingOptionCard>
    );
};
