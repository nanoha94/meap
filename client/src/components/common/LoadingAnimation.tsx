'use client';
import React from 'react';
import { LoaderCircle } from 'lucide-react';
import { usePathname } from 'next/navigation';

import { colors } from '@/constants';
import { useGlobalStore } from '@/stores';

const LoadingAnimation = () => {
    const loadingCount = useGlobalStore(state => state.loadingCount);
    const visibleLoadingAnimation = useGlobalStore(state => state.visibleLoadingAnimation);
    const resetLoadingCount = useGlobalStore(state => state.resetLoadingCount);
    const pathname = usePathname();
    const prevPath = React.useRef<string>(pathname);

    React.useEffect(() => {
        if (prevPath.current !== pathname) {
            // ページ遷移時にローディング状態をリセット
            resetLoadingCount();
        }
        prevPath.current = pathname;
    }, [pathname, resetLoadingCount]);

    // ローディング中かつ表示条件がtrueの時のみ表示
    return loadingCount > 0 && visibleLoadingAnimation ? (
        <div className="fixed z-50 top-0 left-0 w-full h-screen flex justify-center items-center bg-black/50">
            <div className="py-10 px-20 bg-white rounded-xl flex flex-col items-center gap-y-5">
                <LoaderCircle
                    size={30}
                    strokeWidth={2.5}
                    color={colors.primary.main}
                    className="animate-[spin_1.5s_linear_infinite]"
                />
                <p className="text-center text-lg font-bold">Loading...</p>
            </div>
        </div>
    ) : (
        <></>
    );
};

export default LoadingAnimation;
