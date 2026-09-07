import { BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';

export type PlanActionHandlers = {
    onSubscribe: () => void;
    onPortal: () => void;
    onResume: () => void;
};

export type PlanActionButtonConfig = {
    label: string;
    onClick?: () => void;
    variant: (typeof BUTTON_VARIANT)[keyof typeof BUTTON_VARIANT];
    colorVariant?: (typeof COLOR_VARIANT)['GRAY'];
    disabled: boolean;
};
