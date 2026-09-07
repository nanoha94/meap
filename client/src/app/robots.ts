import type { MetadataRoute } from 'next';

import { isNoindexEnabled } from '@/constants';

export default function robots(): MetadataRoute.Robots {
    if (isNoindexEnabled()) {
        return {
            rules: {
                userAgent: '*',
                disallow: '/',
            },
        };
    }

    return {
        rules: {
            userAgent: '*',
            allow: '/',
        },
    };
}
