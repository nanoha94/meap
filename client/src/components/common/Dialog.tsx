'use client';

import React, { useEffect, useRef, useState } from 'react';
import { X } from 'lucide-react';

import { colors } from '@/constants';
import { useGlobalStore } from '@/stores';
import { DialogData } from '@/types';

type DialogPanelProps = {
    dialog: DialogData;
};

const DialogPanel = ({ dialog }: DialogPanelProps) => {
    const { config, onClose } = dialog;
    const footerRef = useRef<HTMLDivElement>(null);
    const [footerHeight, setFooterHeight] = useState(0);

    useEffect(() => {
        if (!config.footer) {
            setFooterHeight(0);
            return;
        }
        const el = footerRef.current;
        if (!el) {
            setFooterHeight(0);
            return;
        }
        setFooterHeight(el.clientHeight);
    }, [config.footer]);

    return (
        <div
            onClick={onClose}
            className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div
                onClick={e => e.stopPropagation()}
                className={`absolute top-10 left-1/2 -translate-x-1/2 ${config.maxWidth ? `max-w-[${config.maxWidth}px]` : 'max-w-[500px]'} w-[calc(100%-40px)] max-h-[calc(100vh-80px)] flex flex-col bg-white rounded-xl overflow-visible`}>
                <div className="px-5 py-3 flex justify-between items-center gap-x-5 text-xl border-b border-gray-border">
                    {config.title}
                    <div className="flex items-center gap-x-4">
                        {config.customButton}
                        <button
                            onClick={onClose}
                            className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                            <X size={32} color={colors.black} />
                        </button>
                    </div>
                </div>
                <div className={`p-5 flex-1 overflow-y-auto ${config.childrenWrapperClassName ?? ''}`} style={{ marginBottom: footerHeight }}>
                    {config.children}
                </div>
                {config.footer && (
                    <div ref={footerRef} className="absolute bottom-0 left-0 w-full">
                        {config.footer}
                    </div>
                )}
            </div>
        </div>
    );
};

const Dialog = () => {
    const dialogs = useGlobalStore(state => state.dialogs);
    if (dialogs.length <= 0) return <></>;

    return dialogs.map(v => <DialogPanel key={v.config.title} dialog={v} />);
};

export default Dialog;
