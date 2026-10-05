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

/** Square Web Payments SDK（本番・Sandbox） */
const SQUARE_SCRIPT_ORIGINS = [
    'https://web.squarecdn.com',
    'https://sandbox.web.squarecdn.com',
];
/** カードトークン化（PCI 接続） */
const SQUARE_CONNECT_ORIGINS = [
    'https://pci-connect.squareup.com',
    'https://pci-connect.squareupsandbox.com',
];
/** Vercel Toolbar（コメント）のオリジン。https://vercel.com/docs/vercel-toolbar/managing-toolbar */
const VERCEL_LIVE_ORIGIN = 'https://vercel.live';

/**
 * Content-Security-Policy を組み立てる。
 * DiceBear SVG（dangerouslySetInnerHTML）と Next.js のインラインスクリプトのため
 * style-src / script-src に 'unsafe-inline' を含める。
 * Square のカード入力は squarecdn のスクリプト・iframe と pci-connect への通信が必要。
 * Vercel Toolbar は vercel.live のスクリプト・iframe と Pusher への通信が必要。
 */
function buildContentSecurityPolicy() {
    const backendOrigin =
        parseOrigin(BACKEND_URL) ?? 'https://localhost:8000';
    const squareScriptOrigins = SQUARE_SCRIPT_ORIGINS.join(' ');
    const squareConnectOrigins = SQUARE_CONNECT_ORIGINS.join(' ');

    const directives = [
        "default-src 'self'",
        `script-src 'self' 'unsafe-inline' ${squareScriptOrigins} ${VERCEL_LIVE_ORIGIN}`,
        `style-src 'self' 'unsafe-inline' ${VERCEL_LIVE_ORIGIN}`,
        `img-src 'self' blob: data: ${backendOrigin} https://*.r2.cloudflarestorage.com ${VERCEL_LIVE_ORIGIN} https://vercel.com`,
        `font-src 'self' ${VERCEL_LIVE_ORIGIN} https://assets.vercel.com`,
        `connect-src 'self' ${backendOrigin} ${squareConnectOrigins} ${VERCEL_LIVE_ORIGIN} wss://ws-us3.pusher.com`,
        `frame-src ${squareScriptOrigins} ${VERCEL_LIVE_ORIGIN}`,
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
