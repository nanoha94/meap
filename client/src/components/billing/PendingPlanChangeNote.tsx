'use client';

import React from 'react';

import { BILLING_PLAN_LABEL } from '@/constants';
import { IPendingPlanChange } from '@/types';
import { getPendingPlanChangeMessage } from '@/utils';

interface Props {
    currentPlanLabel: string;
    pendingPlanChange: IPendingPlanChange;
    className?: string;
    size?: 'sm' | 'base';
}

const PendingPlanChangeNote = ({
    currentPlanLabel,
    pendingPlanChange,
    className,
    size = 'sm',
}: Props) => {
    return (
        <div
            className={`rounded-lg border border-alert-light bg-alert-background px-3 py-2 ${size === 'base' ? 'text-base' : 'text-sm'} ${className ?? ''}`}>
            <p className="mb-1 font-bold text-alert-main">プラン変更予定</p>
            <p>
                {getPendingPlanChangeMessage(
                    currentPlanLabel,
                    BILLING_PLAN_LABEL[pendingPlanChange.nextPlan],
                    pendingPlanChange.changesAt,
                )}
            </p>
        </div>
    );
};

export default PendingPlanChangeNote;
