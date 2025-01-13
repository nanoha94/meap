import Link from 'next/link';
import ApplicationLogo from '@/components/ApplicationLogo';

export const metadata = {
    title: 'Laravel',
};

interface Props {
    children: React.ReactNode;
}

const Layout = ({ children }: Props) => {
    return (
        <div className="max-w-xl mx-auto pt-10 pb-20 px-5 flex flex-col gap-y-16">
            <Link href="/" className="w-fit mx-auto block">
                <ApplicationLogo className="w-60 h-auto fill-current text-gray-500" />
            </Link>
            {children}
        </div>
    );
};

export default Layout;
