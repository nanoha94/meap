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
            {
                protocol: 'https',
                hostname: 'localhost',
                port: '8000',
                pathname: '/storage/**',
            },
            ...(process.env.NEXT_PUBLIC_R2_HOSTNAME
                ? [
                      {
                          protocol: 'https',
                          hostname: process.env.NEXT_PUBLIC_R2_HOSTNAME,
                          pathname: '/**',
                      },
                  ]
                : []),
        ],
    },
};

module.exports = nextConfig;
