import dayjs from 'dayjs';

/**
 * 検索パラメータから日付情報を取得
 * @param searchParams 検索パラメータ { date?: string }
 * @returns { date: string; year: number; month: number }
 */
export const getDateFromSearchParams = (searchParams: {
    date?: string | null;
}): { date: string; year: number; month: number } => {
    const now = new Date();
    const dateParam = searchParams?.date;

    if (dateParam) {
        const parsed = dayjs(dateParam);
        if (parsed.isValid()) {
            return {
                date: parsed.format('YYYY-MM-DD'),
                year: parsed.year(),
                month: parsed.month() + 1,
            };
        }
    }

    return {
        date: dayjs(now).format('YYYY-MM-DD'),
        year: now.getFullYear(),
        month: now.getMonth() + 1,
    };
};
