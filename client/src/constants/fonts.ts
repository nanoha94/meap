import { Noto_Sans_JP } from 'next/font/google';

export const NOTO_SANS_JP = Noto_Sans_JP({
    subsets: ['latin'],
    weight: ['400', '700'],
    variable: '--font-noto-sans-jp',
    preload: false,
});
