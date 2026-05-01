import { COLOR_VARIANT } from '@/constants';

export const getLinkButtonClassName = (
    colorVariant:
        | (typeof COLOR_VARIANT)['PRIMARY']
        | (typeof COLOR_VARIANT)['GRAY'],
) => {
    const colorMappings = {
        primary:
            'text-white bg-primary-main hover:text-gray-main hover:bg-primary-light',
        gray: 'text-gray-dark bg-white border border-gray-main hover:bg-gray-light',
    };
    return `py-2 px-3 w-fit flex items-center gap-x-1 text-base font-bold rounded transition-colors shadow-card ${colorMappings[colorVariant]
        } disabled:opacity-50 disabled:pointer-events-none`;
};
