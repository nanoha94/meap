'use client';

import React from 'react';
import { TextButton, } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, LINK_TO } from '@/constants';
import { ChevronRight } from 'lucide-react';
import { useAiUsageStore } from '@/stores';

interface Props {
    className?: string;
}

/**
 * AI 利用上限到達時のアップセル導線（プラン管理・サブスク開始・ヘルプリンク）
 */
const AiUsageLimitUpsell = ({ className }: Props) => {
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);
    return (
        <div
            className={`ml-auto flex w-full flex-col gap-y-2 ${className ?? ''}`}>
            <p className="flex flex-wrap justify-end text-sm text-alert-main">
                <span>※AI利用回数の上限に達しました</span>
                {aiUsageStatus && (
                    <span>
                        （月間残り{aiUsageStatus.monthlyRemaining}/
                        {aiUsageStatus.monthlyLimit}回
                        {aiUsageStatus.packRemaining >= 0
                            ? `、買い切り残り ${aiUsageStatus.packRemaining} 回`
                            : ''}
                        ）
                    </span>
                )}
            </p>
            <TextButton
                type={BUTTON_TYPE.BUTTON}
                variant={BUTTON_VARIANT.FILLED}
                href={LINK_TO.SETTINGS.BILLING} className='self-end'>
                プラン変更・買い切りパック購入
                <ChevronRight size={20} />
            </TextButton>
        </div>
    );
};

export default AiUsageLimitUpsell;
