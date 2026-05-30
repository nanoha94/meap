'use client';
import React from 'react';
import Image from 'next/image';
import { Control, Controller, FieldValues, Path } from 'react-hook-form';
import { ImagePlus, Trash2 } from 'lucide-react';

import { useSnackbars } from '@/hooks';
import { IImageWithFile, ImageEditFieldStyleConfig } from '@/types';
import {
    ImageCompressionError,
    validateOriginalImageSize,
} from '@/utils/imageCompression';

interface Props<T extends FieldValues> {
    control: Control<T>;
    name: Path<T>;
    styleConfig?: Partial<ImageEditFieldStyleConfig>;
    className?: string;
}

const defaultStyleConfig: ImageEditFieldStyleConfig = {
    iconSmSize: 32,
    iconMdSize: 40,
    imageRounded: 'rounded-none md:rounded-lg',
    containerClass: 'aspect-[4/3] bg-gray-light',
    labelClass: 'gap-y-4 text-gray-main',
    overlayIconContainerClass: 'gap-x-6',
    overlayIconClass: 'p-4',
};

const ImageEditField = <T extends FieldValues>({
    control,
    name,
    styleConfig: styleConfigOverride,
    className,
}: Props<T>) => {
    const { addSnackbar } = useSnackbars();
    const styleConfig = { ...defaultStyleConfig, ...styleConfigOverride };
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    // nameプロパティから一意のIDを生成（配列のインデックスやネストされたパスに対応）
    const inputId = React.useMemo(
        () => `file-input-${String(name).replace(/\./g, '-')}`,
        [name],
    );

    /**
     * サムネイル画像を変更（サイズ検証後、元ファイルをフォームへ反映。圧縮はアップロード時）
     * @param file 画像ファイル
     * @returns void
     */
    const handleChangeImage = (
        file: File | null,
        onChange: (value: IImageWithFile) => void,
        currentValue: IImageWithFile | null | undefined,
    ) => {
        if (!file) {
            return;
        }

        try {
            validateOriginalImageSize(file);
        } catch (error) {
            const message =
                error instanceof ImageCompressionError
                    ? error.message
                    : '画像の選択に失敗しました';
            addSnackbar('error', message);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const img = new window.Image();

        img.onload = () => {
            // 古いobjectURLを解放
            if (currentValue?.file && currentValue?.src) {
                URL.revokeObjectURL(currentValue.src);
            }
            onChange({
                file,
                src: objectUrl,
                width: img.width,
                height: img.height,
            });
        };
        img.src = objectUrl;
    };

    /**
     * サムネイル画像を削除
     * @returns void
     */
    const handleDeleteImage = (
        onChange: (value: IImageWithFile) => void,
        currentValue: IImageWithFile | null | undefined,
    ) => {
        // 古いobjectURLを解放
        if (currentValue?.file && currentValue?.src) {
            URL.revokeObjectURL(currentValue.src);
        }
        onChange({
            file: null,
            src: '',
            width: 0,
            height: 0,
        });
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    return (
        <div className={`flex flex-col gap-y-1 w-full ${className ?? ''}`}>
            <Controller
                control={control}
                name={name}
                render={({ field: { onChange, value } }) => (
                    <div className={`relative w-full h-auto transition-opacity ${styleConfig.containerClass} ${styleConfig.imageRounded}`}>
                        {/* サムネイルが設定されている場合 */}
                        {value?.src && value?.src?.length > 0 ? (
                            <>
                                <Image
                                    src={value.src}
                                    alt={name}
                                    width={value.width}
                                    height={value.height}
                                    className={`absolute top-0 left-0 w-full h-full object-cover ${styleConfig.imageRounded}`}
                                />
                                <div
                                    className={`absolute top-0 left-0 w-full h-full flex items-center justify-center ${styleConfig.overlayIconContainerClass}`}>
                                    <div className="relative group">
                                        <label
                                            htmlFor={inputId}
                                            className={`${styleConfig.overlayIconClass} inline-block cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70`}>
                                            <ImagePlus
                                                size={
                                                    styleConfig.iconSmSize
                                                }
                                                strokeWidth={1.5}
                                            />
                                        </label>
                                        <span className="absolute left-1/2 -translate-x-1/2 -top-10 px-2 py-1 text-white bg-black rounded pointer-events-none transition-opacity delay-200 whitespace-nowrap opacity-0 group-hover:opacity-100">
                                            画像を変更
                                        </span>
                                    </div>
                                    <div className="relative group">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleDeleteImage(
                                                    onChange,
                                                    value,
                                                )
                                            }
                                            className={`${styleConfig.overlayIconClass} cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70`}>
                                            <Trash2
                                                size={
                                                    styleConfig.iconSmSize
                                                }
                                            />
                                        </button>
                                        <span className="absolute left-1/2 -translate-x-1/2 -top-10 px-2 py-1 text-white bg-black rounded pointer-events-none transition-opacity delay-200 whitespace-nowrap opacity-0 group-hover:opacity-100">
                                            画像を削除
                                        </span>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <label
                                htmlFor={inputId}
                                className={`absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center cursor-pointer ${styleConfig.labelClass} ${styleConfig.imageRounded} hover:opacity-70`}>
                                <ImagePlus
                                    size={styleConfig.iconMdSize}
                                    strokeWidth={1.5}
                                />
                                <span>画像を設定</span>
                            </label>
                        )}
                        <input
                            ref={fileInputRef}
                            type="file"
                            id={inputId}
                            accept="image/*"
                            hidden
                            onChange={e => {
                                handleChangeImage(
                                    e.target.files?.[0] ?? null,
                                    onChange,
                                    value,
                                );
                            }}
                            name={name}
                        />
                    </div>
                )}
            />
        </div>
    );
};

export default ImageEditField;
