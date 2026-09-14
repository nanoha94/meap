import type { MetadataRoute } from 'next';

import { FRONTEND_BASE_URL } from '@/constants';

const PUBLIC_PATHS = [
    { path: '/', priority: 1 },
    { path: '/terms', priority: 0.5 },
    { path: '/privacy', priority: 0.5 },
    { path: '/legal/commercial', priority: 0.5 },
    { path: '/help/plan-change', priority: 0.5 },
] as const;

export default function sitemap(): MetadataRoute.Sitemap {
    return PUBLIC_PATHS.map(({ path, priority }) => ({
        url: new URL(path, FRONTEND_BASE_URL).toString(),
        lastModified: new Date(),
        changeFrequency: 'monthly',
        priority,
    }));
}
