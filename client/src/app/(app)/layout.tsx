'use client';

import { useAuth } from '@/hooks';
import Navigation2 from '@/app/(app)/Navigation';
import Loading from '@/app/(app)/Loading';
import { Navigation } from './_components';

interface Props {
    children: React.ReactNode;
}

const AppLayout = ({ children }: Props) => {
    const { user } = useAuth({ middleware: 'auth' });

    if (!user) {
        return <Loading />;
    }

    return (
        <div className="h-screen flex flex-col">
            <Navigation2 user={user} />
            <div className="flex-1 bg-primary-background">{children}</div>
            <Navigation />
        </div>
    );
};

export default AppLayout;
