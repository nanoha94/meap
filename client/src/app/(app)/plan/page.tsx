import Header from '@/app/(app)/_components/Header';
import { CalendarHeader } from './_components';

export const metadata = {
    title: 'Laravel - Plan',
};

const Plan = () => {
    return (
        <>
            <Header title="Plan" />
            <main>
                <CalendarHeader />
            </main>
        </>
    );
};

export default Plan;
