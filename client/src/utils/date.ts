import dayjs from 'dayjs';

/** UI 表示用の日付フォーマット（YYYY/MM/DD） */
export const formatDisplayDate = (date: string): string =>
    dayjs(date).format('YYYY/MM/DD');
