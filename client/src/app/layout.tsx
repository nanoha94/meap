import '@/styles/global.css';
import { LoadingAnimation, Snackbars } from '@/components/common';
import { notoSansJp } from '@/constants';

const RootLayout = ({ children }) => {
    return (
        <html lang="en" className={notoSansJp.variable}>
            <body className="text-base text-black">
                {children}
                <Snackbars />
                <LoadingAnimation />
            </body>
        </html>
    );
};

export const metadata = {
    title: 'Laravel',
};

export default RootLayout;
