<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class LocalizationHelper
{
    /**
     * 現在のロケールを取得
     */
    public static function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * ロケールを設定
     */
    public static function setLocale(string $locale): void
    {
        App::setLocale($locale);
    }

    /**
     * サポートされているロケールの一覧を取得
     */
    public static function getSupportedLocales(): array
    {
        return ['ja', 'en'];
    }

    /**
     * ロケールがサポートされているかチェック
     */
    public static function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, self::getSupportedLocales());
    }

    /**
     * デフォルトロケールを取得
     */
    public static function getDefaultLocale(): string
    {
        return 'ja';
    }

    /**
     * ユーザーの言語設定に基づいてロケールを設定
     */
    public static function setLocaleFromUser($user): void
    {
        if ($user && $user->language) {
            $locale = $user->language;
            if (self::isLocaleSupported($locale)) {
                self::setLocale($locale);
            }
        }
    }

    /**
     * リクエストヘッダーからロケールを設定
     */
    public static function setLocaleFromRequest($request): void
    {
        $locale = $request->header('Accept-Language');
        if ($locale) {
            // Accept-Languageヘッダーから最初の言語を取得
            $locale = explode(',', $locale)[0];
            $locale = explode(';', $locale)[0];
            $locale = strtolower(trim($locale));

            if (self::isLocaleSupported($locale)) {
                self::setLocale($locale);
            }
        }
    }
}
