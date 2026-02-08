"use client";
import React from 'react';
import { ja } from 'date-fns/locale/ja';
import { Calendar } from 'lucide-react';
import DatePicker from 'react-datepicker';

import { colors } from '@/constants';

interface Props {
    value: Date;
    onChange: (date: Date | null, event?: React.MouseEvent<HTMLElement> | React.KeyboardEvent<HTMLElement> | undefined) => void;
}

/**
 * 日付選択フィールド
 * @param value - 日付
 * @param onChange - 日付変更時のコールバック
 * @returns 
 */
const ReadOnlyInput = React.forwardRef<HTMLInputElement, React.InputHTMLAttributes<HTMLInputElement>>(
    ({ value, onClick, onKeyDown, ...props }, ref) => (
        <input
            ref={ref}
            value={value ?? ""}
            onClick={onClick}
            onKeyDown={(e) => {
                if (e.key !== "Escape") {
                    e.preventDefault();
                }
                onKeyDown?.(e);
            }}
            {...props}
            readOnly
        />
    )
);
ReadOnlyInput.displayName = "ReadOnlyInput";

/**
 * カレンダーのヘッダー
 * @param monthDate - 月
 * @param decreaseMonth - 前の月へ移動
 * @param increaseMonth - 次の月へ移動
 * @param prevMonthButtonDisabled - 前の月へ移動が無効かどうか
 * @param nextMonthButtonDisabled - 次の月へ移動が無効かどうか
 * @returns 
 */
const CalendarCustomHeader = ({
    monthDate,
    decreaseMonth,
    increaseMonth,
    prevMonthButtonDisabled,
    nextMonthButtonDisabled,
}: {
    monthDate: Date;
    decreaseMonth: () => void;
    increaseMonth: () => void;
    prevMonthButtonDisabled: boolean;
    nextMonthButtonDisabled: boolean;
}) => (
    <>
        <button
            type="button"
            className="react-datepicker__navigation react-datepicker__navigation--previous"
            onClick={decreaseMonth}
            disabled={prevMonthButtonDisabled}
            aria-label="前の月"
        >
            <span className="react-datepicker__navigation-icon react-datepicker__navigation-icon--previous before:!top-0" />
        </button>
        <h2 className="react-datepicker__current-month">
            {monthDate.getFullYear()}年{monthDate.getMonth() + 1}月
        </h2>
        <button
            type="button"
            className="react-datepicker__navigation react-datepicker__navigation--next"
            onClick={increaseMonth}
            disabled={nextMonthButtonDisabled}
            aria-label="次の月"
        >
            <span className="react-datepicker__navigation-icon react-datepicker__navigation-icon--next before:!top-0" />
        </button>
    </>
);

/**
 * 日付選択フィールド
 * @param value - 日付
 * @param onChange - 日付変更時のコールバック
 * @returns 
 */
const StyuledDatePicker = ({ value, onChange }: Props) => {
    return (
        <div className="relative cursor-pointer rounded-lg transition-colors group hover:bg-gray-light">
            <DatePicker
                selected={value}
                onChange={onChange}
                dateFormat="yyyy/MM/dd（E）"
                locale={ja}
                placeholderText="日付を選択"
                customInput={
                    <ReadOnlyInput className="py-2 px-4 w-full flex-1 bg-white rounded-lg border border-gray-main cursor-pointer" />
                }
                renderCustomHeader={CalendarCustomHeader}
            />
            <div className="absolute p-1 right-2 top-1/2 -translate-y-1/2 rounded-full transition-colors pointer-events-none group-hover:bg-gray-light">
                <Calendar color={colors.gray.main}
                    size={24}
                    strokeWidth={1.5} />
            </div>
        </div>
    );
};

export default StyuledDatePicker;
