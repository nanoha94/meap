'use client';

import React from 'react';

import { useRouter } from 'next/navigation';

import {
    BillingFeatureList,
    BillingOptionCard,
    Button,
    PendingPlanChangeNote,
} from '@/components';
import {
    BILLING_PLAN_DETAILS,
    BILLING_PLAN_LABEL,
    BILLING_PLAN_ORDER,
    BillingPlanDetail,
    BUTTON_TYPE,
    COLOR_VARIANT,
} from '@/constants';
import { useAlertDialog, useBillingApi, useDialog } from '@/hooks';
import {
    IBillingStatus,
    PlanActionButtonConfig,
} from '@/types';
import { formatYen, getPlanActionButtonConfig } from '@/utils';

interface Props {
    billingStatus: IBillingStatus | null;
}

const SubscriptionPlanChange = ({
    billingStatus,
}: Props) => {
    const router = useRouter();
    const { openAlertDialog } = useAlertDialog();
    const { closeDialog } = useDialog();
    const {
        createSubscription,
        createPortalSession,
        resumeSubscription,
    } = useBillingApi();

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
                if (success) {
                    router.refresh();
                    closeDialog(false);
                }
            },
        );
    }, [
        billingStatus,
        closeDialog,
        openAlertDialog,
        resumeSubscription,
        router,
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
                                    onSubscribe: createSubscription,
                                    onPortal: createPortalSession,
                                    onResume: handleResume,
                                },
                            )}
                        />
                    );
                })}
            </div>
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
            variant={isCurrentPlan ? 'current' : 'default'}
            badge={
                isCurrentPlan
                    ? {
                        label: '現在のプラン',
                        colorVariant: COLOR_VARIANT.SECONDARY,
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
            }>
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
