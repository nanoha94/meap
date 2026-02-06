/**
 * 検索パラメータから年月を取得（サーバー・クライアント共通）
 * @param searchParams 検索パラメータ { year?: string; month?: string }
 * @returns { year: number; month: number } 年月
 */
export const getYearMonthFromSearchParams = (
    searchParams: { year?: string | null; month?: string | null },
): { year: number; month: number } => {
    const now = new Date();
    const yearParam = searchParams?.year;
    const monthParam = searchParams?.month;

    const year = yearParam ? parseInt(String(yearParam), 10) : now.getFullYear();
    const month = monthParam ? parseInt(String(monthParam), 10) : now.getMonth() + 1;

    const validYear = Number.isInteger(year) && year >= 1900 && year <= 2100 ? year : now.getFullYear();
    const validMonth = Number.isInteger(month) && month >= 1 && month <= 12 ? month : now.getMonth() + 1;

    return { year: validYear, month: validMonth };
};
