export enum DayOfWeek {
    SUNDAY = 0,
    MONDAY = 1,
    TUESDAY = 2,
    WEDNESDAY = 3,
    THURSDAY = 4,
    FRIDAY = 5,
    SATURDAY = 6,
}

export const DAY_OF_WEEK_LIST: { id: number; name: string }[] = [
    { id: DayOfWeek.SUNDAY, name: '日' },
    { id: DayOfWeek.MONDAY, name: '月' },
    { id: DayOfWeek.TUESDAY, name: '火' },
    { id: DayOfWeek.WEDNESDAY, name: '水' },
    { id: DayOfWeek.THURSDAY, name: '木' },
    { id: DayOfWeek.FRIDAY, name: '金' },
    { id: DayOfWeek.SATURDAY, name: '土' },
];
