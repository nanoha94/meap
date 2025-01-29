'use client';

import { DAY_OF_WEEK_LIST, DayOfWeek } from '@/constants';
import { colors } from '@/constants/colors';
import dayjs, { Dayjs } from 'dayjs';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import React from 'react';

const dayColor = (day: number) => {
    if (day === DayOfWeek.SUNDAY) {
        return 'text-red';
    }
    if (day === DayOfWeek.SATURDAY) {
        return 'text-blue';
    }
    return 'text-black';
};

const dateStyle = (isToday: boolean, day: number) => {
    if (isToday) {
        return 'min-w-6 h-6 flex justify-center items-center text-sm font-bold text-white bg-primary-main rounded-full';
    }
    return dayColor(day);
};

const CalendarHeader = () => {
    const [selectedDate, setSelectedDate] = React.useState<Dayjs>(dayjs());
    // TODO: 月曜始まりに対応する場合、ここを変更する
    const startOfWeek = DayOfWeek.MONDAY;

    const dayOfWeeks = React.useMemo(
        () => [
            ...DAY_OF_WEEK_LIST.slice(startOfWeek),
            ...DAY_OF_WEEK_LIST.slice(0, startOfWeek),
        ],
        [startOfWeek],
    );

    const startOfMonth = React.useMemo(
        () => dayjs(selectedDate).startOf('month'),
        [selectedDate],
    );

    const endOfMonth = React.useMemo(
        () => dayjs(selectedDate).endOf('month'),
        [selectedDate],
    );

    const moveToToday = () => {
        setSelectedDate(dayjs());
    };

    const moveToNextMonth = () => {
        setSelectedDate(prev => prev.add(1, 'month'));
    };

    const moveToPreviousMonth = () => {
        setSelectedDate(prev => prev.add(-1, 'month'));
    };

    const days: (Dayjs | null)[] = React.useMemo(() => {
        const daysArray = [
            ...Array.from({
                length: (startOfMonth.day() + Math.abs(7 - startOfWeek)) % 7,
            }).map(() => null), // 前月の空白
            ...Array.from({ length: endOfMonth.date() }, (_, i) =>
                startOfMonth.add(i, 'day'),
            ),
        ];

        // 7の倍数個になるように
        const extraNulls = (7 - (daysArray.length % 7)) % 7;
        return [
            ...daysArray,
            ...Array.from({ length: extraNulls }).map(() => null),
        ];
    }, [startOfMonth, endOfMonth]);

    return (
        <>
            <div className="relative py-2 pr-5 pl-3">
                <button
                    onClick={moveToToday}
                    className="px-2 py-1 text-base font-bold text-primary-main rounded-full transition-colors hover:bg-gray-light">
                    今日
                </button>
                <div className="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 w-fit flex items-center gap-x-5">
                    <button
                        onClick={moveToPreviousMonth}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <ChevronLeft color={colors.primary.main} size={24} />
                    </button>
                    <span className="whitespace-nowrap">
                        {selectedDate.format('YYYY年MM月')}
                    </span>
                    <button
                        onClick={moveToNextMonth}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <ChevronRight color={colors.primary.main} size={24} />
                    </button>
                </div>
            </div>
            <div className="grid grid-cols-7">
                {dayOfWeeks.map((v, idx) => (
                    <div
                        key={v.id}
                        className={`py-1 text-base ${dayColor(v.id)} text-center bg-white border-y ${idx < 6 ? 'border-r' : ''} border-gray-light`}>
                        {v.name}
                    </div>
                ))}
                {days.map((v, idx) => (
                    <button
                        key={idx}
                        onClick={() => setSelectedDate(v)}
                        className={`py-1 min-h-[50px] flex justify-center border-b ${idx % 7 < 6 ? 'border-r' : ''} border-gray-light transition-colors ${v?.isSame(selectedDate, 'day') ? 'bg-primary-light pointer-events-none' : 'bg-white hover:bg-primary-background'}`}>
                        <div
                            className={`min-h-6 w-fit text-base ${dateStyle(v?.isSame(dayjs(), 'day'), v?.day())}`}>
                            {v ? v.date() : ''}
                        </div>
                    </button>
                ))}
            </div>
        </>
    );
};

export default CalendarHeader;
