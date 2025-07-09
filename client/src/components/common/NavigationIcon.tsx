import React from 'react';
import { NavigationItemType, NAVIGATION_ICON_TYPES, colors } from '@/constants';
import { BookOpenCheck, CalendarDate, CookingPot, Settings } from '../svg';

interface NavigationIconProps {
    iconType: NavigationItemType;
    isCurrentPage: boolean;
}

export const NavigationIcon: React.FC<NavigationIconProps> = ({
    iconType,
    isCurrentPage,
}) => {
    const iconStrokeColor = () => {
        return isCurrentPage ? colors.primary.main : colors.black;
    };

    const iconFillColor = () => {
        return isCurrentPage ? colors.primary.light : colors.gray.iconFill;
    };

    switch (iconType) {
        case NAVIGATION_ICON_TYPES.CALENDAR:
            return (
                <CalendarDate
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.COOKING_POT:
            return (
                <CookingPot
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.BOOK_OPEN_CHECK:
            return (
                <BookOpenCheck
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.SETTINGS:
            return (
                <Settings
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        default:
            return null;
    }
};
