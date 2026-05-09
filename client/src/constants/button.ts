import { COLOR_VARIANT } from "./colors";

export const BUTTON_TYPE = {
    SUBMIT: 'submit',
    BUTTON: 'button',
    RESET: 'reset',
} as const;
export type ButtonType = (typeof BUTTON_TYPE)[keyof typeof BUTTON_TYPE];

export const BUTTON_VARIANT = {
    FILLED: 'filled',
    OUTLINED: 'outlined',
} as const;
export type ButtonVariant = (typeof BUTTON_VARIANT)[keyof typeof BUTTON_VARIANT];

export const BUTTON_SIZE = {
    NORMAL: 'normal',
    SMALL: 'small',
} as const;
export type ButtonSize = (typeof BUTTON_SIZE)[keyof typeof BUTTON_SIZE];

export const PRIMARY_BUTTON_COLOR_CLASS: Record<
    ButtonVariant,
    Record<NonNullable<
        | (typeof COLOR_VARIANT)['PRIMARY']
        | (typeof COLOR_VARIANT)['GRAY']
        | (typeof COLOR_VARIANT)['ALERT']
    >, string>
> = {
    filled: {
        [COLOR_VARIANT.PRIMARY]:
            'text-white bg-primary-main hover:text-primary-main hover:bg-primary-light',
        [COLOR_VARIANT.GRAY]: 'text-white bg-gray-main hover:text-gray-main hover:bg-gray-light',
        [COLOR_VARIANT.ALERT]: 'text-white bg-alert-main hover:text-alert-main hover:bg-alert-light',
    },
    outlined: {
        [COLOR_VARIANT.PRIMARY]:
            'text-primary-main border-2 border-primary-main hover:bg-primary-light',
        [COLOR_VARIANT.GRAY]: 'text-gray-main border-2 border-gray-main hover:bg-gray-light',
        [COLOR_VARIANT.ALERT]: 'text-alert-main border-2 border-alert-main hover:bg-alert-light',
    },
};