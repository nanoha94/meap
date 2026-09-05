<?php

namespace App\Helpers;

use App\Exceptions\SafeUrlFetchException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * SSRF 対策付き HTTPS URL フェッチャー。
 *
 * - スキームは https のみ
 * - DNS 解決後の IP が private / loopback / link-local / reserved なら拒否
 * - HTTP リダイレクトは追従しない
 */
class SafeUrlFetcher
{
    /** レスポンスボディの最大バイト数（5MB） */
    public const MAX_RESPONSE_BYTES = 5 * 1024 * 1024;

    /** 一部 CDN・サイトが要求するブラウザ風 User-Agent */
    public const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private const DEFAULT_TIMEOUT_SECONDS = 30;

    /**
     * URL が安全にフェッチ可能か検証する。
     *
     * @return string|null 拒否理由（ユーザー向けメッセージ）。安全なら null
     */
    public static function validateUrl(string $url): ?string
    {
        $parsed = parse_url($url);

        // スキーム・ホストが取れない URL はフェッチ対象にできない
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            return __('validation.url_not_accessible');
        }

        // http や file 等を拒否し、平文通信・非 HTTP スキーム経由の SSRF を防ぐ
        if (strtolower($parsed['scheme']) !== 'https') {
            return __('validation.url_https_required', ['attribute' => 'url']);
        }

        // userinfo 付き URL は認証情報の漏えい・解析の複雑化を招くため拒否
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return __('validation.url_not_accessible');
        }

        $host = strtolower($parsed['host']);

        // localhost は DNS 解決前に拒否（ループバックへ到達するホスト名を防ぐ）
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return __('validation.url_not_accessible');
        }

        $ips = self::getIpAddressesForHost($host);

        // 解決できないホストは内部向け・存在しない宛先の可能性があるため拒否
        if ($ips === []) {
            return __('validation.url_not_accessible');
        }

        // A/AAAA レコードのいずれかが private / loopback / link-local / reserved なら拒否
        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                return __('validation.url_not_accessible');
            }
        }

        return null;
    }

    /**
     * SSRF 対策付きで URL のレスポンスボディを取得する。
     *
     * @throws SafeUrlFetchException 検証失敗・HTTP 失敗・サイズ超過時
     */
    public static function fetch(
        string $url,
        int $maxBytes = self::MAX_RESPONSE_BYTES,
    ): string {
        $validationError = self::validateUrl($url);

        // 検証エラーがある場合は例外を投げる
        if ($validationError !== null) {
            throw SafeUrlFetchException::validationFailed($validationError);
        }

        // HTTP リクエストを送信する
        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->withHeaders(['User-Agent' => self::DEFAULT_USER_AGENT])
                ->get($url);
        } catch (Throwable $e) {
            throw SafeUrlFetchException::requestFailed($e);
        }

        // リクエストが失敗した場合は例外を投げる
        if (! $response->successful()) {
            throw SafeUrlFetchException::badResponse($response);
        }

        $body = $response->body();

        // レスポンスボディが空か、サイズが最大バイト数を超えている場合は例外を投げる
        if ($body === '' || strlen($body) > $maxBytes) {
            throw SafeUrlFetchException::invalidBody(strlen($body), $maxBytes);
        }

        return $body;
    }

    /**
     * private / loopback / link-local / reserved の IP かどうか。
     */
    public static function isBlockedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    /**
     * ホスト名または IP リテラルに対応する IP アドレス一覧を返す。
     *
     * @return list<string>
     */
    public static function getIpAddressesForHost(string $host): array
    {
        // IPv6 リテラル（[2001:db8::1]）の角括弧を除去
        $normalizedHost = trim($host, '[]');

        // ホストが IP 文字列そのものなら DNS 解決は不要
        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            return [$normalizedHost];
        }

        $ips = [];

        // IPv4（A）と IPv6（AAAA）を同時に取得し、validateUrl で全アドレスを検査できるようにする
        $records = @dns_get_record($normalizedHost, DNS_A | DNS_AAAA);

        if (is_array($records)) {
            foreach ($records as $record) {
                // A レコード（IPv4）
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }

                // AAAA レコード（IPv6）
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // dns_get_record が使えない・失敗した環境向けに IPv4 のみフォールバック
        if ($ips === []) {
            // ホスト名に対応するIPv4アドレスを取得
            $resolved = gethostbyname($normalizedHost);

            // IP に変換できた場合のみ採用
            if ($resolved !== $normalizedHost && filter_var($resolved, FILTER_VALIDATE_IP)) {
                $ips[] = $resolved;
            }
        }

        // 同一 IP の重複を除いて返す
        return array_values(array_unique($ips));
    }
}
