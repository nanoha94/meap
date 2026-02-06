'use client';
import React from 'react';
import dayjs, { Dayjs } from 'dayjs';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { DAY_OF_WEEK, getDayOfWeekTextColor } from '@/constants/calendar';
import { colors } from '@/constants';
import { useMonthlyCalendar } from '@/hooks';

interface Props {
    dots: string[][];
    /** 表示月（URL と同期する場合に page から渡す） */
    year: number;
    month: number;
    onMonthChange: (year: number, month: number) => void;
    selectedDate: Dayjs;
    onDateSelect: (date: Dayjs | ((prev: Dayjs) => Dayjs)) => void;
}

/**
 * 日付のスタイルを返す
 * @param isToday 今日の場合はtrue、それ以外はfalse
 * @param day 曜日（0:日曜日, 1:月曜日, 2:火曜日, 3:水曜日, 4:木曜日, 5:金曜日, 6:土曜日）
 * @returns 日付のスタイル
 */
const dateStyle = (isToday: boolean, day: number) => {
    if (isToday) {
        return 'pr-[1px] pb-[2px] min-w-6 h-6 flex items-center justify-center text-sm font-bold text-white bg-primary-main rounded-full';
    }
    return getDayOfWeekTextColor(day);
};

const MonthlyCalendar = ({ dots, year, month, onMonthChange, onDateSelect, selectedDate }: Props) => {
    const { dayOfWeeks, days, moveToToday, moveToNextMonth, moveToPreviousMonth } =
        useMonthlyCalendar(
            DAY_OF_WEEK.MONDAY, // 月曜始まり
            selectedDate,       // 選択日
            { year, month },    // 表示月
            onDateSelect,       // 選択日変更
            onMonthChange       // 表示月変更
        );

    return (
        <>
            <div className="relative py-2 pr-5 pl-3">
                <button
                    onClick={moveToToday}
                    className="px-2 py-1 font-bold text-primary-main rounded-full transition-colors hover:bg-gray-light">
                    今日
                </button>
                <div className="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 w-fit flex items-center gap-x-5">
                    <button
                        onClick={moveToPreviousMonth}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <ChevronLeft color={colors.primary.main} size={24} />
                    </button>
                    <span className="whitespace-nowrap">
                        {selectedDate?.format('YYYY年MM月')}
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
                        className={`py-1 ${getDayOfWeekTextColor(v.id)} text-center bg-white border-y ${idx < 6 ? 'border-r' : ''} border-gray-light`}>
                        {v.name}
                    </div>
                ))}
                {days.map((v, idx) => {
                    const dotConfigs = v?.date() ? dots[v.date() - 1] : [];
                    return (
                        <button
                            key={idx}
                            onClick={() => v && onDateSelect?.(v)}
                            disabled={!v}
                            className={`py-1 min-h-[50px] flex flex-col items-center gap-y-2.5 border-b ${idx % 7 < 6 ? 'border-r' : ''} border-gray-light transition-colors ${!v ? 'bg-gray-background' : v?.isSame(selectedDate, 'day') ? 'bg-primary-light pointer-events-none' : 'bg-white hover:bg-primary-background'}`}>
                            <div
                                className={`leading-none ${dateStyle(v?.isSame(dayjs(), 'day') ?? false, v?.day() ?? -1)}`}>
                                {v ? v.date() : ''}
                            </div>
                            <div className='flex gap-1'>{dotConfigs?.map(v => <span key={v} className={`w-2 h-2 block rounded-full`} style={{ backgroundColor: v }} />)}</div>
                        </button>
                    );
                })}
            </div>
        </>
    );
};

export default MonthlyCalendar;
