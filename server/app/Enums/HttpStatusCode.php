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
            self::BAD_REQUEST => '不正なリクエスト',
            self::UNAUTHORIZED => '認証が必要です',
            self::FORBIDDEN => 'アクセスが拒否されました',
            self::NOT_FOUND => 'リソースが見つかりません',
            self::METHOD_NOT_ALLOWED => '許可されていないメソッドです',
            self::CONFLICT => 'リソースが競合しています',
            self::UNPROCESSABLE_ENTITY => '入力内容に誤りがあります',
            self::TOO_MANY_REQUESTS => 'リクエストが多すぎます',
            self::INTERNAL_SERVER_ERROR => 'サーバー内部エラーが発生しました',
            self::NOT_IMPLEMENTED => '実装されていない機能です',
            self::BAD_GATEWAY => 'ゲートウェイエラーが発生しました',
            self::SERVICE_UNAVAILABLE => 'サービスが利用できません',
            self::GATEWAY_TIMEOUT => 'ゲートウェイがタイムアウトしました',
            default => '不明なエラー'
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
