import '@/styles/global.css';
import { LoadingAnimation, Snackbars } from '@/components/common';
import { SnackbarsProvider } from '@/contexts/useSnackbars';
import { notoSansJp } from '@/constants';

const RootLayout = ({ children }) => {
    return (
        <html lang="en" className={notoSansJp.variable}>
            <SnackbarsProvider>
                <body className="text-base text-black">
                    {children}
                    <Snackbars />
                    <LoadingAnimation />
                </body>
            </SnackbarsProvider>
        </html>
    );
};

export const metadata = {
    title: 'Laravel',
};

export default RootLayout;
