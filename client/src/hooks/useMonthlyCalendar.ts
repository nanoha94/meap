"use client";
import React from "react";
import dayjs, { Dayjs } from "dayjs";
import { DAY_OF_WEEK_LIST, DayOfWeek } from "@/constants";

export const useMonthlyCalendar = (
    startOfWeek: DayOfWeek, 
    selectedDate: Dayjs,
    current: {  
        year: number,
        month: number,
    },
    onDateSelect?: (date: Dayjs | ((prev: Dayjs) => Dayjs)) => void,
    onMonthChange?: (year: number, month: number) => void,
) => {
      // 曜日のリスト
      const dayOfWeeks = React.useMemo(
        () => [
            ...DAY_OF_WEEK_LIST.slice(startOfWeek),
            ...DAY_OF_WEEK_LIST.slice(0, startOfWeek),
        ],
        [startOfWeek],
    );

    // 月の最初の日・最後の日（selectedDate が useEffect で year/month と同期している）
    const startOfMonth = React.useMemo(
        () => dayjs(selectedDate).startOf('month'),
        [selectedDate],
    );

    const endOfMonth = React.useMemo(
        () => dayjs(selectedDate).endOf('month'),
        [selectedDate],
    );

    
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
    }, [startOfMonth, endOfMonth, startOfWeek]);

    /** 親が毎レンダーで新しいコールバックを渡すと effect / 安定した参照が必要な箇所でズレるため ref で参照する */
    const onDateSelectRef = React.useRef(onDateSelect);
    React.useEffect(() => {
        onDateSelectRef.current = onDateSelect;
    }, [onDateSelect]);

    const onMonthChangeRef = React.useRef(onMonthChange);
    React.useEffect(() => {
        onMonthChangeRef.current = onMonthChange;
    }, [onMonthChange]);

    /**
     * 今日に移動（表示月を今月にして、選択日も今日にする）
     */
    const moveToToday = React.useCallback(() => {
        const now = dayjs();
        onDateSelectRef.current?.(now);
        onMonthChangeRef.current?.(now.year(), now.month() + 1);
    }, []);

    /**
     * 次の月に移動
     */
    const moveToNextMonth = React.useCallback(() => {
        if (
            onMonthChangeRef.current &&
            current.year !== undefined &&
            current.month !== undefined
        ) {
            const next = dayjs()
                .year(current.year)
                .month(current.month - 1)
                .add(1, 'month');
            onMonthChangeRef.current(next.year(), next.month() + 1);
        } else {
            onDateSelectRef.current?.((prev) => prev.add(1, 'month'));
        }
    }, [current.year, current.month]);

    /**
     * 前の月に移動
     */
    const moveToPreviousMonth = React.useCallback(() => {
        if (
            onMonthChangeRef.current &&
            current.year !== undefined &&
            current.month !== undefined
        ) {
            const prev = dayjs()
                .year(current.year)
                .month(current.month - 1)
                .add(-1, 'month');
            onMonthChangeRef.current(prev.year(), prev.month() + 1);
        } else {
            onDateSelectRef.current?.((prev) => prev.add(-1, 'month'));
        }
    }, [current.year, current.month]);

    // URL と同期時は表示月が変わっても「日」はそのまま維持（同じ日付を新月中で有効な範囲に収める）
    React.useEffect(() => {
        if (current.year !== undefined && current.month !== undefined) {
            onDateSelectRef.current?.(prev => {
                const newMonthStart = dayjs().year(current.year).month(current.month - 1);
                const day = Math.min(prev.date(), newMonthStart.endOf('month').date());
                return newMonthStart.date(day);
            });
        }
    }, [current.year, current.month]);


    return {
        dayOfWeeks,
        days,
        moveToToday,
        moveToNextMonth,
        moveToPreviousMonth,
    };
};
