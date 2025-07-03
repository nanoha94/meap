import { Header, Navigation } from '@/components/common';
import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

const Loading = () => {
    return (
        <div className="h-screen flex flex-col">
            <div className="flex-1 bg-primary-background">
                <Header title="Loading..." />
                <div className="py-20">
                    <LoaderCircle
                        size={60}
                        color={colors.primary.main}
                        className="animate-spin mx-auto"
                    />
                </div>
            </div>
            <Navigation />
        </div>
    );
};

export default Loading;
