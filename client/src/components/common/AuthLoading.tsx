import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

/**
 * 認証ページ用のローディングコンポーネント
 * Suspenseのfallbackなどで使用
 */
const AuthLoading = () => {
    return (
        <div className="py-10 px-20 bg-white rounded-xl flex flex-col items-center gap-y-5">
            <LoaderCircle
                size={60}
                strokeWidth={2.5}
                color={colors.primary.main}
                className="animate-[spin_1.5s_linear_infinite]"
            />
            <p className="text-center text-2xl font-bold">Loading...</p>
        </div>
    );
};

export default AuthLoading;
