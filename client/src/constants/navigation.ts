// アイコンタイプの定数定義
export const NAVIGATION_ICON_TYPES = {
    CALENDAR: 'calendar',
    COOKING_POT: 'cooking-pot',
    BOOK_OPEN_CHECK: 'book-open-check',
    SETTINGS: 'settings',
} as const;

export type NavigationItemType =
    (typeof NAVIGATION_ICON_TYPES)[keyof typeof NAVIGATION_ICON_TYPES];

export interface NavigationItem {
    link: string;
    name: string;
    iconType: NavigationItemType;
}

export const navigationItems: NavigationItem[] = [
    {
        link: '/plan',
        name: '献立表',
        iconType: NAVIGATION_ICON_TYPES.CALENDAR,
    },
    {
        link: '/recipe',
        name: '料理/レシピ',
        iconType: NAVIGATION_ICON_TYPES.COOKING_POT,
    },
    {
        link: '/shopping-list',
        name: '買い物リスト',
        iconType: NAVIGATION_ICON_TYPES.BOOK_OPEN_CHECK,
    },
    {
        link: '/settings',
        name: '設定',
        iconType: NAVIGATION_ICON_TYPES.SETTINGS,
    },
];
