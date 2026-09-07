/**
 * カラー設計（tailwind.config.js と同期）
 *
 * primary.main (#8A6A4E)
 *   - ボタン（保存・追加・購入等）、本文リンク、UI 状態（ナビ・今日・選択）、操作アイコン
 * primary.light / background
 *   - ページ地、選択中の面、ホバー地
 *
 * gray   - キャンセル・副次ボタン、補助テキスト
 * alert  - 削除ボタン、エラー
 * accent - 「おすすめ」バッジのみ
 * secondary - 装飾のみ（ボタンに使わない）
 */
import resolveConfig from 'tailwindcss/resolveConfig';
import tailwindConfig from '../../tailwind.config.js';

// Tailwindの設定を解析して取得
const config = resolveConfig(tailwindConfig);

export const colors = config.theme?.colors;

export const COLOR_VARIANT = {
    PRIMARY: 'primary',
    SECONDARY: 'secondary',
    ACCENT: 'accent',
    GRAY: 'gray',
    CATEGORY: 'category',
    ALERT: 'alert',
    SUCCESS: 'success',
} as const;
export type ColorVariant =
    (typeof COLOR_VARIANT)[keyof typeof COLOR_VARIANT];