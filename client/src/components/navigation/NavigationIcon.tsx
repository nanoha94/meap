import React from 'react';
import { BookOpenCheck, CalendarDate, CookingPot, Settings } from '../svg';

import { NAVIGATION_ICON_TYPES, NavigationItemType, colors } from '@/constants';

interface NavigationIconProps {
    className?: string;
    strokeWidth?: number;
    iconType: NavigationItemType;
    isCurrentPage: boolean;
}

const NavigationIcon: React.FC<NavigationIconProps> = ({
    className,
    strokeWidth = 2.5,
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
                    className={className}
                    strokeWidth={strokeWidth}
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.COOKING_POT:
            return (
                <CookingPot
                    className={className}
                    strokeWidth={strokeWidth}
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.BOOK_OPEN_CHECK:
            return (
                <BookOpenCheck
                    className={className}
                    strokeWidth={strokeWidth}
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        case NAVIGATION_ICON_TYPES.SETTINGS:
            return (
                <Settings
                    className={className}
                    strokeWidth={strokeWidth}
                    strokeColor={iconStrokeColor()}
                    fillColor={iconFillColor()}
                />
            );
        default:
            return null;
    }
};

export default NavigationIcon;
