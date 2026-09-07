import { BUTTON_VARIANT, COLOR_VARIANT, PRIMARY_BUTTON_COLOR_CLASS } from '@/constants';

export const getLinkButtonClassName = (
    colorVariant:
        | (typeof COLOR_VARIANT)['PRIMARY']
        | (typeof COLOR_VARIANT)['GRAY'] = COLOR_VARIANT.PRIMARY,
) => {
    const colorClasses =
        colorVariant === COLOR_VARIANT.PRIMARY
            ? PRIMARY_BUTTON_COLOR_CLASS[BUTTON_VARIANT.FILLED][COLOR_VARIANT.PRIMARY]
            : 'text-black bg-white border border-gray-main hover:bg-gray-light';
    return `py-2 px-3 w-fit flex items-center gap-x-1 text-base font-bold rounded transition-colors shadow-card ${colorClasses} disabled:opacity-50 disabled:pointer-events-none`;
};
