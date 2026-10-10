import { colors } from './colors';
import { CardStyle } from '@/types/vendor/square';

/** Web Payments SDK Card フォームの見た目（全画面共通） */
export const SQUARE_CARD_ELEMENT_STYLE: CardStyle = {
    '.input-container': {
        borderColor: colors.gray.main,
        borderRadius: '8px',
        borderWidth: '1px',
    },
    '.input-container.is-focus': {
        borderColor: colors.black,
        borderWidth: '2px',
    },
    '.input-container.is-error': {
        borderColor: colors.alert.main,
        borderWidth: '2px',
    },
    input: {
        backgroundColor: colors.white,
        color: colors.black,
        fontSize: '16px',
    },
    'input::placeholder': {
        color: colors.gray.placeholder,
    },
    'input.is-error': {
        color: colors.alert.main,
    },
    // 案内文（「カード番号を入力してください」）は入力後も残るため隠す。
    // 検証エラーだけ表示する。fontSize は message 系セレクタでは指定できない。
    '.message-text': {
        color: 'transparent',
    },
    '.message-icon': {
        color: 'transparent',
    },
    '.message-text.is-error': {
        color: colors.alert.main,
    },
    '.message-icon.is-error': {
        color: colors.alert.main,
    },
};

export const SQUARE_SCRIPT_URL =
    process.env.NEXT_PUBLIC_SQUARE_SCRIPT_URL ?? '';

export const SQUARE_APPLICATION_ID =
    process.env.NEXT_PUBLIC_SQUARE_APPLICATION_ID ?? '';

export const SQUARE_LOCATION_ID =
    process.env.NEXT_PUBLIC_SQUARE_LOCATION_ID ?? '';
