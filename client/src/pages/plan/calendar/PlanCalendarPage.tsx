'use client';

import { Header, MonthlyCalendar } from '@/components';
import EmptyButton from '@/components/EmptyButton';

const PlanCalendarPage = () => {
    return (
        <>
            <Header title="献立表" />
            <main className='pb-[60px] lg:px-10 max-w-[1000px] mx-auto'>
                <MonthlyCalendar />
                <div className="py-5 flex">
                    <EmptyButton href="/plan/new" />
                </div>
            </main>
        </>
    );
};

export default PlanCalendarPage;
