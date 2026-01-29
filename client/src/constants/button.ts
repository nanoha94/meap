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
