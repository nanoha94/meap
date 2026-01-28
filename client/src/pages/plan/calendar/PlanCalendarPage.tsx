'use client';
import { Header } from '@/components/common';
import MonthlyCalendar from '@/models/plan/components/MonthlyCalendar';

const PlanCalendarPage = () => {
    return (
        <>
            <Header title="献立表" />
            <main className='pb-[60px] lg:px-10 max-w-[1000px] mx-auto'>
                <MonthlyCalendar />
            </main>
        </>
    );
};

export default PlanCalendarPage;
