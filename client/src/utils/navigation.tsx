import React from 'react';
import { NavigationItemType } from '@/constants';
import {
    BookOpenCheck,
    CalendarDate,
    CookingPot,
    Settings,
} from '@/components/svg';

export const renderNavigationIcon = (
    iconType: NavigationItemType,
    strokeColor: string,
    fillColor: string,
): React.ReactNode => {
    switch (iconType) {
        case 'calendar':
            return (
                <CalendarDate strokeColor={strokeColor} fillColor={fillColor} />
            );
        case 'cooking-pot':
            return (
                <CookingPot strokeColor={strokeColor} fillColor={fillColor} />
            );
        case 'book-open-check':
            return (
                <BookOpenCheck
                    strokeColor={strokeColor}
                    fillColor={fillColor}
                />
            );
        case 'settings':
            return <Settings strokeColor={strokeColor} fillColor={fillColor} />;
        default:
            return null;
    }
};
