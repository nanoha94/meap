/** @type {import('next').NextConfig} */

const BACKEND_URL =
    process.env.NEXT_PUBLIC_BACKEND_URL || 'https://localhost:8000';

function parseOrigin(url) {
    try {
        return new URL(url).origin;
    } catch {
        return null;
    }
}

/** payjp.js とカード入力 iframe のオリジン */
const PAYJP_JS_ORIGIN = 'https://js.pay.jp';
/** カードトークン作成 API のオリジン */
const PAYJP_API_ORIGIN = 'https://api.pay.jp';
/** Vercel Toolbar（コメント）のオリジン。https://vercel.com/docs/vercel-toolbar/managing-toolbar */
const VERCEL_LIVE_ORIGIN = 'https://vercel.live';

/**
 * Content-Security-Policy を組み立てる。
 * DiceBear SVG（dangerouslySetInnerHTML）と Next.js のインラインスクリプトのため
 * style-src / script-src に 'unsafe-inline' を含める。
 * PAY.JP のカード入力は js.pay.jp のスクリプトと iframe、api.pay.jp への通信が必要。
 * Vercel Toolbar は vercel.live のスクリプト・iframe と Pusher への通信が必要。
 */
function buildContentSecurityPolicy() {
    const backendOrigin =
        parseOrigin(BACKEND_URL) ?? 'https://localhost:8000';

    const directives = [
        "default-src 'self'",
        `script-src 'self' 'unsafe-inline' ${PAYJP_JS_ORIGIN} ${VERCEL_LIVE_ORIGIN}`,
        `style-src 'self' 'unsafe-inline' ${VERCEL_LIVE_ORIGIN}`,
        `img-src 'self' blob: data: ${backendOrigin} https://*.r2.cloudflarestorage.com ${VERCEL_LIVE_ORIGIN} https://vercel.com`,
        `font-src 'self' ${VERCEL_LIVE_ORIGIN} https://assets.vercel.com`,
        `connect-src 'self' ${backendOrigin} ${PAYJP_API_ORIGIN} ${VERCEL_LIVE_ORIGIN} wss://ws-us3.pusher.com`,
        `frame-src ${PAYJP_JS_ORIGIN} ${VERCEL_LIVE_ORIGIN}`,
        "frame-ancestors 'none'",
        "form-action 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        'upgrade-insecure-requests',
    ];

    return directives.join('; ');
}

const nextConfig = {
    async headers() {
        const securityHeaders = [
            { key: 'X-Frame-Options', value: 'DENY' },
            { key: 'X-Content-Type-Options', value: 'nosniff' },
            {
                key: 'Referrer-Policy',
                value: 'strict-origin-when-cross-origin',
            },
        ];

        if (process.env.NODE_ENV === 'production') {
            securityHeaders.push({
                key: 'Strict-Transport-Security',
                value: 'max-age=31536000; includeSubDomains',
            });
            securityHeaders.push({
                key: 'Content-Security-Policy',
                value: buildContentSecurityPolicy(),
            });
        }

        return [
            {
                source: '/:path*',
                headers: securityHeaders,
            },
        ];
    },
    images: {
        // ローカル環境（next dev）でのみ最適化を無効化
        unoptimized: process.env.NODE_ENV === 'development',
        remotePatterns: [
            // ローカル開発（public ディスク）の APP_URL/storage/...
            {
                protocol: 'https',
                hostname: 'localhost',
                port: '8000',
                pathname: '/storage/**',
            },
            // R2 署名付き URL — AWS_URL 未設定で AWS_ENDPOINT 直接利用時
            // 例: https://<account_id>.r2.cloudflarestorage.com/<bucket>/images/...?X-Amz-...
            {
                protocol: 'https',
                hostname: '*.r2.cloudflarestorage.com',
                pathname: '/**',
            },
        ],
    },
};

module.exports = nextConfig;
