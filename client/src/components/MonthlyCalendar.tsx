'use client';
import React from 'react';
import dayjs, { Dayjs } from 'dayjs';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { DAY_OF_WEEK, DAY_OF_WEEK_LIST, colors } from '@/constants';

interface Props {
    dots: string[][];
}

/**
 * 曜日の色を返す
 * @param day 曜日（0:日曜日, 1:月曜日, 2:火曜日, 3:水曜日, 4:木曜日, 5:金曜日, 6:土曜日）
 * @returns 曜日の色
 */
const dayColor = (day: number) => {
    if (day === DAY_OF_WEEK.SUNDAY) {
        return 'text-red';
    }
    if (day === DAY_OF_WEEK.SATURDAY) {
        return 'text-blue';
    }
    return 'text-black';
};

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
    return dayColor(day);
};

const MonthlyCalendar = ({ dots }: Props) => {
    const [selectedDate, setSelectedDate] = React.useState<Dayjs>(dayjs());
    // TODO: 月曜始まりに対応する場合、ここを変更する
    const startOfWeek = DAY_OF_WEEK.MONDAY;

    // 曜日のリスト
    const dayOfWeeks = React.useMemo(
        () => [
            ...DAY_OF_WEEK_LIST.slice(startOfWeek),
            ...DAY_OF_WEEK_LIST.slice(0, startOfWeek),
        ],
        [startOfWeek],
    );

    // 月の最初の日
    const startOfMonth = React.useMemo(
        () => dayjs(selectedDate).startOf('month'),
        [selectedDate],
    );

    // 月の最後の日
    const endOfMonth = React.useMemo(
        () => dayjs(selectedDate).endOf('month'),
        [selectedDate],
    );

    /**
     * 今日に移動
     */
    const moveToToday = () => {
        setSelectedDate(dayjs());
    };

    /**
     * 次の月に移動
     */
    const moveToNextMonth = () => {
        setSelectedDate(prev => prev.add(1, 'month'));
    };

    /**
     * 前の月に移動
     */
    const moveToPreviousMonth = () => {
        setSelectedDate(prev => prev.add(-1, 'month'));
    };

    /**
     * 日付のリスト
     */
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
                        className={`py-1 ${dayColor(v.id)} text-center bg-white border-y ${idx < 6 ? 'border-r' : ''} border-gray-light`}>
                        {v.name}
                    </div>
                ))}
                {days.map((v, idx) => {
                    const dotConfigs = v?.date() ? dots[v.date() - 1] : [];
                    return (
                        <button
                            key={idx}
                            onClick={() => v && setSelectedDate(v)}
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
