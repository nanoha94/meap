'use client';

import React from 'react';

import { BillingFeatureList, BillingOptionCard, Button } from '@/components';
import {
    BILLING_PACK_OPTIONS,
    BILLING_PACK_TYPE,
    BillingPackDetail,
    BUTTON_TYPE,
    COLOR_VARIANT,
} from '@/constants';
import { useBillingApi } from '@/hooks';
import { useAiUsageStore } from '@/stores';
import { formatPackUnitPrice, formatYen } from '@/utils';

const PackPurchase = () => {
    const { purchasePack } = useBillingApi();
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);

    return (
        <div className="flex w-full flex-col gap-y-6">
            <div className="flex flex-col gap-y-2 text-sm leading-relaxed text-gray-main">
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
                        onPurchase={() => purchasePack(pack.type)}
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
                <dt className="mb-1 text-xs font-bold text-gray-main">
                    付与される AI 利用回数
                </dt>
                <dd className="text-lg font-bold">{detail.credits} 回</dd>
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
