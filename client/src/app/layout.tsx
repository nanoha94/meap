import { Noto_Sans_JP } from 'next/font/google';
import '@/app/global.css';

const notoSansJp = Noto_Sans_JP({
    subsets: ['latin'],
    weight: ['400', '700'],
    variable: '--font-notoSansJp',
});

const RootLayout = ({ children }) => {
    return (
        <html lang="en" className={notoSansJp.variable}>
            <body className="text-black">{children}</body>
        </html>
    );
};

export const metadata = {
    title: 'Laravel',
};

export default RootLayout;
