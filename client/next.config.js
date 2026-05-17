/** @type {import('next').NextConfig} */
const nextConfig = {
    images: {
        // 開発環境でのみ最適化を無効化
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
