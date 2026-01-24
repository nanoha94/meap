import resolveConfig from 'tailwindcss/resolveConfig';
import tailwindConfig from '../../tailwind.config.js';

// Tailwindの設定を解析して取得
const config = resolveConfig(tailwindConfig);

export const colors = config.theme?.colors;

export enum COLOR_VARIANT {
    PRIMARY = 'primary',
    SECONDARY = 'secondary',
    ACCENT = 'accent',
    GRAY = 'gray',
    CATEGORY = 'category',
    ALERT = 'alert',
    SUCCESS = 'success',
}