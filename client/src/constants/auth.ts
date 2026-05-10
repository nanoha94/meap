import { LINK_TO } from './links';

/**
 * (auth) レイアウト配下でもセッション必須とするパス（ゲスト許可の例外）
 */
export const SESSION_REQUIRED_PATHS_IN_AUTH_SHELL = [
    LINK_TO.EMAIL_VERIFY,
] as const;
