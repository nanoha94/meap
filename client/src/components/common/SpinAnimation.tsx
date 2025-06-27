import { colors } from '@/constants/colors';
import { LoaderCircle } from 'lucide-react';

const SpinAnimation = () => {
    return (
        <div className="py-5">
            <LoaderCircle
                size={40}
                color={colors.primary.main}
                className="animate-spin mx-auto"
            />
        </div>
    );
};

export default SpinAnimation;
