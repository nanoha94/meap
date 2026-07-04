import { create } from 'zustand';

import { IAiUsageStatus } from '@/types';

interface AiUsageState {
    aiUsageStatus: IAiUsageStatus | null;
    setAiUsageStatus: (status: IAiUsageStatus | null) => void;
}

export const useAiUsageStore = create<AiUsageState>(set => ({
    // initial state
    aiUsageStatus: null,

    // setter func
    setAiUsageStatus: status => {
        set({ aiUsageStatus: status });
    },
}));
