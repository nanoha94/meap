'use client';
import { LoaderCircle } from 'lucide-react';
import Button from './Button';
import { colors } from '@/constants/colors';
import React from 'react';
import { AlertDialogData } from '@/types/dialog';

const AlertDialog = ({
    isOpen,
    config,
    onCancel,
    onAction,
    isLoading = false,
}: AlertDialogData) => {
    if (!isOpen || !config) return <></>;

    return (
        <div
            onClick={onCancel}
            className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div
                onClick={e => e.stopPropagation()}
                className="absolute top-10 left-1/2 -translate-x-1/2 max-w-[500px] w-[calc(100%-40px)] bg-white rounded-xl">
                <div className="px-5 py-12">
                    <div className="mb-7 px-5 py-2 w-full text-2xl font-bold text-center">
                        {config.title}
                    </div>
                    {isLoading ? (
                        <div className="py-5">
                            <LoaderCircle
                                size={40}
                                color={colors.primary.main}
                                className="animate-spin mx-auto"
                            />
                        </div>
                    ) : (
                        <div className="flex flex-col gap-y-7">
                            <div className="flex flex-col gap-y-4">
                                {config.message.map((v, idx) => (
                                    <p
                                        key={idx}
                                        className="text-center whitespace-pre-wrap">
                                        {v}
                                    </p>
                                ))}
                                {config.alertMessage && (
                                    <span className="text-center text-alert-main">
                                        {config.alertMessage}
                                    </span>
                                )}
                            </div>
                            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                                <Button
                                    colorVariant="gray"
                                    variant="outlined"
                                    onClick={onCancel}>
                                    キャンセル
                                </Button>
                                <Button onClick={onAction} colorVariant="alert">
                                    {config.actionButtonText}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AlertDialog;
