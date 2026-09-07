import { IAiUsageStatus } from '@/types';

/** AI 利用回数が制限に達しているかどうかを判定する */
export const isAiLimitReached = (status: IAiUsageStatus | null): boolean => {
    if (!status) {
        return false;
    }

    return status.monthlyRemaining <= 0 && status.packRemaining <= 0;
};
