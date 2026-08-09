import React from 'react';

type DotListProps = {
    items: readonly string[];
};

const DotList = ({ items }: DotListProps) => (
    <ul className="list-none space-y-2">
        {items.map((item) => (
            <li
                key={item}
                className="relative flex items-start gap-2 pl-4 text-base leading-relaxed text-black">
                <span
                    className="absolute left-0 top-[0.625rem] h-1.5 w-1.5 shrink-0 rounded-full bg-accent-main"
                    aria-hidden
                />
                {item}
            </li>
        ))}
    </ul>
);

export default DotList;
