export const TIMEOUT_MS = 30 * 1000; // 30秒

/** 画像アップロード用。モバイル回線での大きめの multipart 送信を許容する */
export const UPLOAD_TIMEOUT_MS = 120 * 1000;

/** AI 解析用。OCR → 構造化の2段処理を許容する */
export const AI_TIMEOUT_MS = 120 * 1000;

export const API_STATUS_CODE = {
    // 成功レスポンス
    OK: 200,
    CREATED: 201,
    NO_CONTENT: 204,

    // クライアントエラー
    BAD_REQUEST: 400,
    UNAUTHORIZED: 401,
    FORBIDDEN: 403,
    NOT_FOUND: 404,
    METHOD_NOT_ALLOWED: 405,
    CONFLICT: 409,
    GONE: 410,
    UNPROCESSABLE_ENTITY: 422,
    TOO_MANY_REQUESTS: 429,

    // サーバーエラー
    INTERNAL_SERVER_ERROR: 500,
    NOT_IMPLEMENTED: 501,
    BAD_GATEWAY: 502,
    SERVICE_UNAVAILABLE: 503,
    GATEWAY_TIMEOUT: 504,
} as const;
export type ApiStatusCode = typeof API_STATUS_CODE[keyof typeof API_STATUS_CODE];
