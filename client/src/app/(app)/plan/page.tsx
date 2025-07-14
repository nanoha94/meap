import { EmailVerifiedHandler, Header } from '@/components/common';
import { CalendarHeader } from './_components';
export const metadata = {
    title: 'Laravel - Plan',
};

const Plan = () => {
    return (
        <>
            <EmailVerifiedHandler />
            <Header title="Plan" />
            <main>
                <CalendarHeader />
            </main>
        </>
    );
};

export default Plan;
