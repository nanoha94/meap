import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

const LoadingAnimation = () => {
    return (
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
    );
};

export default LoadingAnimation;
