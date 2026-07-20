'use client';

import React from 'react';

import { useAiUsageStore } from '@/stores';

/**
 * AI 利用回数を 1 回消費する前の確認文言
 */
const AiUsageConfirmation: React.FC = () => {
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);

    if (!aiUsageStatus) {
        return null;
    }

    const sourceLabel =
        aiUsageStatus.monthlyRemaining > 0
            ? '月間枠から'
            : aiUsageStatus.packRemaining > 0
              ? '買い切り枠から'
              : '';

    return (
        <p className="text-alert-main">
            {sourceLabel}
            AI利用回数を1回消費しますが、よろしいですか？
            <br />
            <span className="text-black">
                （月間残り{aiUsageStatus.monthlyRemaining}/
                {aiUsageStatus.monthlyLimit}回
                {aiUsageStatus.packRemaining >= 0
                    ? `、買い切り残り ${aiUsageStatus.packRemaining} 回`
                    : ''}
                ）
            </span>
        </p>
    );
};

export default AiUsageConfirmation;
