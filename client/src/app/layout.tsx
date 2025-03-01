import { Noto_Sans_JP } from 'next/font/google';
import '@/app/global.css';
import { Snackbars } from '@/components';
import { SnackbarsProvider } from '@/contexts/useSnackbars';

const notoSansJp = Noto_Sans_JP({
    subsets: ['latin'],
    weight: ['400', '700'],
    variable: '--font-notoSansJp',
});

const RootLayout = ({ children }) => {
    return (
        <html lang="en" className={notoSansJp.variable}>
            <SnackbarsProvider>
                <body className="text-black">
                    {children}
                    <Snackbars />
                </body>
            </SnackbarsProvider>
        </html>
    );
};

export const metadata = {
    title: 'Laravel',
};

export default RootLayout;
