import React from 'react';
import { ChevronDown } from 'lucide-react';

import { colors } from '@/constants';

interface Props {
    value: string;
    name: string;
    options: { id: string; name: string }[];
    isShowPlaceholder?: boolean;
    onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
}

const StyledSelect = ({
    value,
    name,
    options,
    onChange,
    isShowPlaceholder = true,
}: Props) => {
    return (
        <div className="relative w-full">
            <select
                value={value}
                onChange={e => {
                    onChange(e);
                }}
                id={name}
                className={`py-2 pl-4 pr-10 w-full border rounded-lg border-gray-main appearance-none cursor-pointer
                    ${value === '' ? 'text-gray-placeholder' : 'text-black'}
                `}>
                {isShowPlaceholder && (
                    <option value="">--選択してください--</option>
                )}
                {options.map(v => (
                    <option key={v.id} value={v.id} className="text-black">
                        {v.name}
                    </option>
                ))}
            </select>
            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <ChevronDown size={20} color={colors.black} />
            </div>
        </div>
    );
};

export default StyledSelect;
