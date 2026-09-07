import React from 'react';
import { LoaderCircle } from 'lucide-react';

import { colors } from '@/constants';

const Loading = () => {
    return (
        <div className="flex min-h-[200px] items-center justify-center bg-primary-background py-20">
            <LoaderCircle
                size={60}
                color={colors.primary.main}
                className="mx-auto animate-spin"
            />
        </div>
    );
};

export default Loading;
