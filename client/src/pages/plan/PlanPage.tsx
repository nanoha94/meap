'use client';
import { Header } from '@/components/common';
import React from 'react';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import { useSnackbars } from '@/hooks/useSnackbars';
import MonthlyCalendar from '@/models/plan/components/MonthlyCalendar';

const PlanPage = () => {
    const router = useRouter();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const { addSnackbar } = useSnackbars();
    const hasProcessed = React.useRef(false);

    React.useEffect(() => {
        if (!hasProcessed.current) {
            if (searchParams?.get('verified') === '1') {
                addSnackbar('success', 'メールアドレス認証が完了しました');
            }

            const newParams = new URLSearchParams(searchParams?.toString());
            newParams.delete('verified');
            router.replace(`${pathname}?${newParams.toString()}`);

            hasProcessed.current = true;
        }
    }, []);

    return (
        <>
            <Header title="献立表" />
            <main>
                <MonthlyCalendar />
            </main>
        </>
    );
};

export default PlanPage;
