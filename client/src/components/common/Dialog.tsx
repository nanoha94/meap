'use client';
import React from 'react';
import { X } from 'lucide-react';

import { colors } from '@/constants';
import { useGlobalStore } from '@/stores';

const Dialog = () => {
    const dialogs = useGlobalStore(state => state.dialogs);
    const currentDialog = dialogs[0] || null;

    if (!currentDialog || !currentDialog.isOpen || !currentDialog.config) return <></>;

    return (
        <div
            onClick={currentDialog.onClose}
            className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div
                onClick={e => e.stopPropagation()}
                className="absolute top-10 left-1/2 -translate-x-1/2 max-w-[500px] w-[calc(100%-40px)] bg-white rounded-xl">
                <div className="px-5 py-3 flex justify-between items-center gap-x-5 text-xl border-b border-gray-border">
                    {currentDialog.config.title}
                    <button
                        onClick={currentDialog.onClose}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <X size={32} color={colors.black} />
                    </button>
                </div>
                <div className="px-5 py-8">{currentDialog.config.children()}</div>
            </div>
        </div>
    );
};

export default Dialog;
