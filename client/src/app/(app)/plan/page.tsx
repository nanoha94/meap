import { Header } from '@/components/common';
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
