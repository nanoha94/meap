import Header from '@/app/(app)/Header';
import { CalendarHeader } from './_components';

export const metadata = {
    title: 'Laravel - Dashboard',
};

const Dashboard = () => {
    return (
        <>
            <Header title="Dashboard" />
            <main className="bg-primary-background">
                <CalendarHeader />
            </main>
        </>
    );
};

export default Dashboard;
