import { BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';

export type PlanActionHandlers = {
    onSubscribe: () => void;
    onDowngrade: () => void;
    onResume: () => void;
};

export type PayjpCardFormChangeState = {
    complete: boolean;
    empty: boolean;
    errorMessage: string | null;
};

export type PayjpCreateTokenResult =
    | { ok: true; token: string }
    | { ok: false; message: string };

export type PayjpCardFormHandle = {
    createToken: () => Promise<PayjpCreateTokenResult>;
};

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
