'use client';

import React from 'react';

import { useRouter } from 'next/navigation';

import {
    BillingFeatureList,
    BillingOptionCard,
    Button,
} from '@/components';
import {
    BILLING_PACK_OPTIONS,
    BILLING_PACK_TYPE,
    BillingPackDetail,
    BUTTON_TYPE,
    COLOR_VARIANT,
} from '@/constants';
import { useAiUsageApi, useBillingApi, useDialog } from '@/hooks';
import { useAiUsageStore } from '@/stores';
import { BillingCheckoutOrderRow, IBillingStatus } from '@/types';
import { formatPackUnitPrice, formatYen } from '@/utils';
import BillingCheckoutPayment from './BillingCheckoutPayment';

interface Props {
    billingStatus: IBillingStatus | null;
}

const buildPackOrderRows = (
    pack: BillingPackDetail,
): BillingCheckoutOrderRow[] => [
        {
            label: 'パック',
            value: pack.label,
        },
        {
            label: '付与 AI 利用回数',
            value: `${pack.credits} 回`,
        },
        {
            label: '今回のお支払い（税込）',
            value: formatYen(pack.price),
            emphasis: 'total',
        },
    ];

const PackPurchase = ({ billingStatus }: Props) => {
    const router = useRouter();
    const { closeAllDialogs, openDialog } = useDialog();
    const { fetchAiUsageStatus } = useAiUsageApi();
    const { purchasePack } = useBillingApi();
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);

    const completePurchase = React.useCallback(
        async (success: boolean) => {
            if (!success) {
                return;
            }

            await fetchAiUsageStatus();
            router.refresh();
            closeAllDialogs();
        },
        [closeAllDialogs, fetchAiUsageStatus, router],
    );

    const handlePurchase = React.useCallback(
        (pack: BillingPackDetail) => {
            if (!billingStatus) {
                return;
            }

            openDialog({
                title: `${pack.label}の購入`,
                children: (
                    <BillingCheckoutPayment
                        billingStatus={billingStatus}
                        orderRows={buildPackOrderRows(pack)}
                        orderNote="購入後、買い切り AI 利用回数が即時付与され、上記金額が請求されます。"
                        submitButtonText="購入する"
                        onSubmit={cardToken =>
                            purchasePack(pack.type, cardToken)
                        }
                        onSuccess={() => {
                            void completePurchase(true);
                        }}
                    />
                ),
                maxWidth: 480,
            });
        },
        [billingStatus, completePurchase, openDialog, purchasePack],
    );

    return (
        <div className="flex w-full flex-col gap-y-6">
            <div className="flex flex-col gap-y-2 leading-relaxed">
                <p>
                    月間枠を使い切った後に消費されます。有効期限はなく、プラン変更・解約の影響も受けません。
                </p>
                {aiUsageStatus && (
                    <p>
                        現在の買い切り残り：
                        <span className="font-bold">
                            {aiUsageStatus.packRemaining} 回
                        </span>
                    </p>
                )}
            </div>

            <div className="flex w-full flex-col gap-y-4 sm:flex-row sm:gap-x-4">
                {BILLING_PACK_OPTIONS.map(pack => (
                    <PackColumn
                        key={pack.type}
                        detail={pack}
                        isRecommended={pack.type === BILLING_PACK_TYPE.VALUE}
                        onPurchase={() => {
                            handlePurchase(pack);
                        }}
                    />
                ))}
            </div>
        </div>
    );
};

export default PackPurchase;

interface PackColumnProps {
    detail: BillingPackDetail;
    isRecommended: boolean;
    onPurchase: () => void;
}

const PackColumn = ({
    detail,
    isRecommended,
    onPurchase,
}: PackColumnProps) => (
    <BillingOptionCard
        badge={
            isRecommended
                ? {
                    label: 'おすすめ',
                    colorVariant: COLOR_VARIANT.ACCENT,
                }
                : undefined
        }
        header={{
            title: detail.label,
            price: formatYen(detail.price),
            subtitle: formatPackUnitPrice(detail.price, detail.credits),
        }}
        footer={
            <Button type={BUTTON_TYPE.BUTTON} onClick={onPurchase}>
                購入する
            </Button>
        }
        className={isRecommended ? 'border-accent-main border-2' : ''}
    >
        <dl className="flex flex-col gap-y-4 text-sm">
            <div>
                <dt className="mb-1 text-gray-main">
                    付与される AI 利用回数
                </dt>
                <dd className="text-lg font-bold">{detail.credits} 回</dd>
            </div>

            <div>
                <dt className="mb-1 text-gray-main">
                    使用可能な AI 機能
                </dt>
                <dd>
                    <BillingFeatureList features={detail.features} />
                </dd>
            </div>
        </dl>
    </BillingOptionCard>
);
