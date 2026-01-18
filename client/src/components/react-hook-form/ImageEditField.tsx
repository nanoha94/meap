'use client';
import React from 'react';
import Image from 'next/image';
import { Control, Controller, FieldValues, Path } from 'react-hook-form';
import { ImagePlus, Trash } from 'lucide-react';
import { MAX_IMAGE_SIZE, STYLE_SIZE } from '@/constants';
import { IImageWithFile } from '@/types';

interface Props<T extends FieldValues> {
    control: Control<T>;
    name: Path<T>;
    size?: (typeof STYLE_SIZE)[keyof typeof STYLE_SIZE];
}

const sizeConfigs = {
    [STYLE_SIZE.SM]: {
        iconSmSize: 20,
        iconMdSize: 32,
        iconSmPadding: 'p-1.5',
        iconsGapX: 'gap-x-2.5',
        iconTextGapY: 'gap-y-1',
    },
    [STYLE_SIZE.LG]: {
        iconSmSize: 32,
        iconMdSize: 40,
        iconSmPadding: 'p-4',
        iconsGapX: 'gap-x-6',
        iconTextGapY: 'gap-y-4',
    },
};

const ImageEditField = <T extends FieldValues>({
    control,
    name,
    size = STYLE_SIZE.LG,
}: Props<T>) => {
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    // nameプロパティから一意のIDを生成（配列のインデックスやネストされたパスに対応）
    const inputId = React.useMemo(
        () => `file-input-${String(name).replace(/\./g, '-')}`,
        [name],
    );

    /**
     * サムネイル画像を変更
     * @param file 画像ファイル
     * @returns void
     */
    const handleChangeImage = (
        file: File | null,
        onChange: (value: IImageWithFile) => void,
        currentValue: IImageWithFile | null | undefined,
    ) => {
        // 画像サイズが大きすぎる場合はエラー通知し、画像は元のままにする
        if (file && file.size > MAX_IMAGE_SIZE) {
            alert(
                `画像サイズが大きすぎます（最大${MAX_IMAGE_SIZE / 1024 / 1024}MB）`,
            );
            return;
        }

        // 画像を設定
        if (file) {
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
        }
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
        <div className="flex flex-col gap-y-1 w-full">
            <Controller
                control={control}
                name={name}
                render={({ field: { onChange, value } }) => (
                    <div className="relative w-full h-auto aspect-[4/3] bg-gray-light rounded-lg transition-opcity">
                        {/* サムネイルが設定されている場合 */}
                        {value?.src && value?.src?.length > 0 ? (
                            <>
                                <Image
                                    src={value.src}
                                    alt={name}
                                    width={value.width}
                                    height={value.height}
                                    className="absolute top-0 left-0 w-full h-full object-cover rounded-lg"
                                />
                                <div
                                    className={`absolute top-0 left-0 w-full h-full flex items-center justify-center ${sizeConfigs[size].iconsGapX}`}>
                                    <div className="relative group">
                                        <label
                                            htmlFor={inputId}
                                            className={`${sizeConfigs[size].iconSmPadding} inline-block cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70`}>
                                            <ImagePlus
                                                size={
                                                    sizeConfigs[size].iconSmSize
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
                                            className={`${sizeConfigs[size].iconSmPadding} cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70`}>
                                            <Trash
                                                size={
                                                    sizeConfigs[size].iconSmSize
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
                            // サムネイルが未設定の場合
                            <label
                                htmlFor={inputId}
                                className={`absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center ${sizeConfigs[size].iconTextGapY} cursor-pointer text-gray-main rounded-lg hover:opacity-70`}>
                                <ImagePlus
                                    size={sizeConfigs[size].iconMdSize}
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
