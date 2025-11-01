<?php

namespace App\Enums;

enum HttpStatusCode: int
{
    // 成功レスポンス
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;

        // クライアントエラー (4xx)
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case GONE = 410;
    case UNPROCESSABLE_ENTITY = 422;
    case TOO_MANY_REQUESTS = 429;

        // サーバーエラー (5xx)
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    /**
     * エラーコードの説明を取得
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::BAD_REQUEST => __('http_status.bad_request'),
            self::UNAUTHORIZED => __('http_status.unauthorized'),
            self::FORBIDDEN => __('http_status.forbidden'),
            self::NOT_FOUND => __('http_status.not_found'),
            self::METHOD_NOT_ALLOWED => __('http_status.method_not_allowed'),
            self::CONFLICT => __('http_status.conflict'),
            self::GONE => __('http_status.gone'),
            self::UNPROCESSABLE_ENTITY => __('http_status.unprocessable_entity'),
            self::TOO_MANY_REQUESTS => __('http_status.too_many_requests'),
            self::INTERNAL_SERVER_ERROR => __('http_status.internal_server_error'),
            self::NOT_IMPLEMENTED => __('http_status.not_implemented'),
            self::BAD_GATEWAY => __('http_status.bad_gateway'),
            self::SERVICE_UNAVAILABLE => __('http_status.service_unavailable'),
            self::GATEWAY_TIMEOUT => __('http_status.gateway_timeout'),
            default => __('http_status.unknown_error')
        };
    }

    /**
     * クライアントエラーかどうかを判定
     */
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * サーバーエラーかどうかを判定
     */
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    /**
     * 認証関連エラーかどうかを判定
     */
    public function isAuthError(): bool
    {
        return in_array($this->value, [401, 403]);
    }

    /**
     * バリデーションエラーかどうかを判定
     */
    public function isValidationError(): bool
    {
        return in_array($this->value, [400, 422]);
    }
}
