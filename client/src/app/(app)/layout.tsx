'use client';

import { useAuth } from '@/hooks/api';
import Navigation2 from '@/components/xxx/Navigation';
import { Navigation } from '@/components/common';
import Loading from './loading';

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
