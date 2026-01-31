import React from 'react';
import { Pencil } from 'lucide-react';

import { colors } from '@/constants';

interface Props {
    value: string;
    placeholder: string;
    onOpenDialog: () => void;
}

/**
 * ダイアログを開いてフィールドを編集するボタン
 * @param value - フィールドの値
 * @param placeholder - プレースホルダー
 * @param onOpenDialog - ダイアログを開く関数
 * @returns 
 */
const DialogField = ({ value, placeholder, onOpenDialog }: Props) => {
    return (
        <button
            type="button"
            className="relative w-full cursor-pointer rounded-lg transition-colors group hover:bg-gray-light"
            onClick={() => {
                onOpenDialog();
            }}>
            <input
                value={value}
                type="text"
                readOnly
                placeholder={placeholder}
                className="py-2 px-4 w-full flex-1 outline-none bg-white rounded-lg border border-gray-main pointer-events-none"
            />
            <div
                className="absolute p-1 right-2 top-1/2 -translate-y-1/2 cursor-pointer rounded-full transition-colors group-hover:bg-gray-light"
            >
                <Pencil
                    color={colors.gray.main}
                    size={24}
                    strokeWidth={1.5}
                />
            </div>
        </button>
    );
};

export default DialogField;