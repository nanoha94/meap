import { BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { VerificationIntent } from '@/types/vendor/square';

export type PlanActionHandlers = {
    onSubscribe: () => void;
    onDowngrade: () => void;
    onResume: () => void;
};

export type CardFormChangeState = {
    complete: boolean;
    empty: boolean;
    errorMessage: string | null;
};

export type CardCreateTokenResult =
    | { ok: true; token: string }
    | { ok: false; message: string };

export type CardFormHandle = {
    createToken: () => Promise<CardCreateTokenResult>;
};

export type BillingTokenizationIntent = VerificationIntent;

export type PlanActionButtonConfig = {
    label: string;
    onClick?: () => void;
    variant: (typeof BUTTON_VARIANT)[keyof typeof BUTTON_VARIANT];
    colorVariant?: (typeof COLOR_VARIANT)['GRAY'];
    disabled: boolean;
};

export type BillingCheckoutOrderRow = {
    label: string;
    value: React.ReactNode;
    emphasis?: 'total';
};
