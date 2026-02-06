export const DAY_OF_WEEK = {
    SUNDAY: 0,
    MONDAY: 1,
    TUESDAY: 2,
    WEDNESDAY: 3,
    THURSDAY: 4,
    FRIDAY: 5,
    SATURDAY: 6,
} as const;
export type DayOfWeek = typeof DAY_OF_WEEK[keyof typeof DAY_OF_WEEK];

/**
 * 曜日に対応するテキスト色の Tailwind クラスを返す（日:赤, 土:青, その他:黒）
 * @param day 曜日（0:日曜日, 1:月曜日, ..., 6:土曜日）
 */
export const getDayOfWeekTextColor = (day: number): string => {
    if (day === DAY_OF_WEEK.SUNDAY) return 'text-red';
    if (day === DAY_OF_WEEK.SATURDAY) return 'text-blue';
    return 'text-black';
};

export const DAY_OF_WEEK_LIST: { id: number; name: string }[] = [
    { id: DAY_OF_WEEK.SUNDAY, name: '日' },
    { id: DAY_OF_WEEK.MONDAY, name: '月' },
    { id: DAY_OF_WEEK.TUESDAY, name: '火' },
    { id: DAY_OF_WEEK.WEDNESDAY, name: '水' },
    { id: DAY_OF_WEEK.THURSDAY, name: '木' },
    { id: DAY_OF_WEEK.FRIDAY, name: '金' },
    { id: DAY_OF_WEEK.SATURDAY, name: '土' },
];
