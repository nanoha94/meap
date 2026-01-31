import React from 'react';
import { LucideProps } from 'lucide-react';

import { COLOR_VARIANT } from '@/constants';

export interface ActionButton {
    label: string;
    icon: React.ReactElement<LucideProps>;
    onClick: () => void;
    color?: (typeof COLOR_VARIANT)['ALERT'];
}
