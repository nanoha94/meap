import { Header } from '@/components/common';
import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

const Loading = () => {
    return (
        <>
            <div className="opacity-50">
                <Header title="Loading..." />
            </div>
            <div className="py-20">
                <LoaderCircle
                    size={60}
                    color={colors.primary.main}
                    className="animate-spin mx-auto"
                />
            </div>
        </>
    );
};

export default Loading;
