import { Header } from '@/components/common';
import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

const Loading = () => {
    return (
        <>
            <Header title="Loading..." />
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
