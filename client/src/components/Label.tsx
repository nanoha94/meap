import { COLOR_VARIANT } from "@/constants";
import React from "react";

interface Props {
    label: string;
    colorVariant:
    | (typeof COLOR_VARIANT)['ACCENT']
    | (typeof COLOR_VARIANT)['SECONDARY']
    | (typeof COLOR_VARIANT)['GRAY'];
    className?: string;
}

const Label = ({ label, colorVariant = COLOR_VARIANT.GRAY, className }: Props) => {
    const colorClasses = React.useMemo(() => {
        const colorMappings = {
            accent: 'bg-accent-light',
            secondary: 'bg-secondary-light',
            gray: 'bg-gray-light',
        };
        return colorMappings[colorVariant];
    }, [colorVariant]);

    return (
        <span className={`px-3 py-1 text-sm font-bold rounded ${colorClasses} ${className ?? ''}`}>
            {label}
        </span>
    );
};

export default Label;