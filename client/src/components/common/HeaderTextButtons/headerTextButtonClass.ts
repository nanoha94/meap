export function getHeaderTextButtonClassName({
    disabled = false,
    colorVariant = 'secondary',
}: {
    disabled?: boolean;
    colorVariant?: 'secondary' | 'gray' | 'accent' | 'alert';
}) {
    const colorMappings = {
        secondary:
            'text-secondary-main border-secondary-main bg-secondary-background hover:bg-secondary-main',
        gray: 'text-gray-main border-gray-main bg-gray-background hover:bg-gray-main',
        accent: 'text-accent-main border-accent-main bg-accent-background hover:bg-accent-main',
        alert: 'text-alert-main border-alert-main bg-alert-background hover:bg-alert-main',
    };
    return `py-1 px-2 w-fit flex items-center gap-x-1 font-bold rounded border-2 transition-colors hover:text-white shadow-card ${
        colorMappings[colorVariant]
    } ${disabled ? 'opacity-50 pointer-events-none' : ''}`;
}
