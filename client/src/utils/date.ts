import dayjs from 'dayjs';

const DATE_ONLY_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

/** UI 表示用の日付フォーマット（YYYY/MM/DD） */
export const formatDisplayDate = (date: string): string => {
    const dateOnly = date.slice(0, 10);
    if (DATE_ONLY_PATTERN.test(dateOnly)) {
        const [year, month, day] = dateOnly.split('-');
        return `${year}/${month}/${day}`;
    }

    return dayjs(date).format('YYYY/MM/DD');
};
