'use client';
import Button from './Button';
import { COLOR_VARIANT } from '@/constants/colors';
import React from 'react';
import { BUTTON_VARIANT } from '@/constants';
import { useGlobalStore } from '@/stores';

const AlertDialog = () => {
    const alertDialogs = useGlobalStore(state => state.alertDialogs);
    const currentDialog = alertDialogs[0] || null;

    if (!currentDialog || !currentDialog.isOpen || !currentDialog.config) return <></>;

    return (
        <div
            onClick={currentDialog.onCancel}
            className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div
                onClick={e => e.stopPropagation()}
                className="absolute top-10 left-1/2 -translate-x-1/2 max-w-[500px] w-[calc(100%-40px)] bg-white rounded-xl">
                <div className="px-5 py-12">
                    <div className="mb-7 px-5 py-2 w-full text-2xl font-bold text-center">
                        {currentDialog.config.title}
                    </div>

                    <div className="flex flex-col gap-y-7">
                        <div className="flex flex-col gap-y-4">
                            {currentDialog.config.message.map((v, idx) => (
                                <p
                                    key={idx}
                                    className="text-center whitespace-pre-wrap">
                                    {v}
                                </p>
                            ))}
                            {currentDialog.config.alertMessage && (
                                <span className="text-center text-alert-main">
                                    {currentDialog.config.alertMessage}
                                </span>
                            )}
                        </div>
                        <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                            <Button
                                colorVariant={COLOR_VARIANT.GRAY}
                                variant={BUTTON_VARIANT.OUTLINED}
                                onClick={currentDialog.onCancel}>
                                キャンセル
                            </Button>
                            <Button onClick={currentDialog.onAction} colorVariant={COLOR_VARIANT.ALERT}>
                                {currentDialog.config.actionButtonText}
                            </Button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    );
};

export default AlertDialog;
