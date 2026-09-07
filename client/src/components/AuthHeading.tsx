import React from 'react';

type Props = {
    children: React.ReactNode;
    as?: 'h1' | 'h2';
};

const AuthHeading = ({ children, as = 'h1' }: Props) => {
    if (as === 'h2') {
        return (
            <div className="flex items-center gap-x-3">
                <span className="h-px flex-1 bg-gray-border" aria-hidden />
                <h2 className="shrink-0 text-sm font-bold text-gray-main">
                    {children}
                </h2>
                <span className="h-px flex-1 bg-gray-border" aria-hidden />
            </div>
        );
    }

    return <h1 className="text-center text-2xl font-bold">{children}</h1>;
};

export default AuthHeading;
