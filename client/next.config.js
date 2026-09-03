/** @type {import('next').NextConfig} */
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
