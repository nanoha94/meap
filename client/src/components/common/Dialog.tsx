'use client';
import React from 'react';
import { X } from 'lucide-react';

import { colors } from '@/constants';
import { useGlobalStore } from '@/stores';

const Dialog = () => {
    const dialogs = useGlobalStore(state => state.dialogs);
    if (dialogs.length <= 0) return <></>;

    return dialogs.map(v => <div
        key={v.config.title}
        onClick={v.onClose}
        className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
        <div
            onClick={e => e.stopPropagation()}
            className={`absolute top-10 left-1/2 -translate-x-1/2 ${v.config.maxWidth ? `max-w-[${v.config.maxWidth}px]` : 'max-w-[500px]'} w-[calc(100%-40px)] max-h-[calc(100vh-80px)] flex flex-col bg-white rounded-xl overflow-visible`}>
            <div className="px-5 py-3 flex justify-between items-center gap-x-5 text-xl border-b border-gray-border">
                {v.config.title}
                <div className="flex items-center gap-x-4">
                    {v.config.customButton}
                    <button
                        onClick={v.onClose}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <X size={32} color={colors.black} />
                    </button>
                </div>
            </div>
            <div className="p-5 flex-1 overflow-y-auto">{v.config.children}</div>
        </div>
    </div>);
};

export default Dialog;
