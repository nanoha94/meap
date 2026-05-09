export const TIMEOUT_MS = 30 * 1000; // 30秒
export const MAX_IMAGE_SIZE = 1 * 1024 * 1024; // 5MB

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
