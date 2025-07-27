import { MAX_IMAGE_SIZE } from '@/constants';
import { IPostRecipeRequest, IRecipe } from '@/types/api/recipe';
import { ImagePlus, Trash } from 'lucide-react';
import Image from 'next/image';
import React from 'react';
import { Control, Controller, useFormContext } from 'react-hook-form';

interface Props {
    control: Control<IPostRecipeRequest>;
    thumbnail?: IRecipe['thumbnail'];
}

type Thumbnail = {
    file: File | null;
    url: string;
    width: number;
    height: number;
};

const ThumbnailEditField = ({ control, thumbnail }: Props) => {
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const { setValue } = useFormContext<IPostRecipeRequest>();
    const [thumbnailState, setThumbnailState] = React.useState<Thumbnail>({
        file: null,
        url: thumbnail?.url ?? '',
        width: thumbnail?.width ?? 0,
        height: thumbnail?.height ?? 0,
    });
    const [isError, setIsError] = React.useState(false);

    const handleChangeThumbnail = (e: React.ChangeEvent<HTMLInputElement>) => {
        // エラーを解除
        setIsError(false);

        const file = e.target.files?.[0];

        // 画像サイズが大きすぎる場合はエラーを表示して画像を削除
        if (file && file.size > MAX_IMAGE_SIZE) {
            setIsError(true);
        }
        if (file) {
            const objectUrl = URL.createObjectURL(file);
            const img = new window.Image();
            img.onload = () => {
                // 古いobjectURLを解放
                if (thumbnailState.file && thumbnailState.url) {
                    URL.revokeObjectURL(thumbnailState.url);
                }
                setThumbnailState({
                    file,
                    url: objectUrl,
                    width: img.width,
                    height: img.height,
                });
            };
            img.src = objectUrl;
        }
        setValue('thumbnail', file ?? null);
    };

    const handleDeleteThumbnail = () => {
        // エラーを解除
        setIsError(false);

        // 古いobjectURLを解放
        if (thumbnailState.file && thumbnailState.url) {
            URL.revokeObjectURL(thumbnailState.url);
        }
        setThumbnailState({
            file: null,
            url: '',
            width: 0,
            height: 0,
        });
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        setValue('thumbnail', null);
    };
    return (
        <div className="flex flex-col gap-y-1">
            <div className="relative w-full h-auto aspect-video bg-gray-light rounded-lg overflow-hidden transition-opcity">
                {/* サムネイルが設定されている場合 */}
                {!!thumbnailState.file ||
                !!(thumbnailState.url && thumbnailState.url.length > 0) ? (
                    <>
                        <Image
                            src={thumbnailState.url}
                            alt="thumbnail"
                            width={thumbnailState.width}
                            height={thumbnailState.height}
                            className="absolute top-0 left-0 w-full h-full object-cover"
                        />
                        <div className="absolute top-0 left-0 w-full h-full flex items-center justify-center gap-x-6">
                            <div className="relative group">
                                <label
                                    htmlFor="file-input"
                                    className="p-4 inline-block cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70">
                                    <ImagePlus size={32} />
                                </label>
                                <span className="absolute left-1/2 -translate-x-1/2 -top-10 px-2 py-1 text-white bg-black rounded pointer-events-none transition-opacity delay-200 whitespace-nowrap opacity-0 group-hover:opacity-100">
                                    画像を変更
                                </span>
                            </div>
                            <div className="relative group">
                                <button
                                    onClick={handleDeleteThumbnail}
                                    className="p-4 cursor-pointer text-white rounded-full bg-gray-main/80 transition-opacity hover:opacity-70">
                                    <Trash size={32} />
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
                        htmlFor="file-input"
                        className="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center gap-y-4 cursor-pointer text-gray-main rounded-lg hover:opacity-70">
                        <ImagePlus size={40} strokeWidth={1.5} />
                        <span>画像を登録する</span>
                    </label>
                )}
                <Controller
                    control={control}
                    name="thumbnail"
                    render={({ field }) => (
                        <input
                            ref={fileInputRef}
                            type="file"
                            id="file-input"
                            accept="image/*"
                            hidden
                            onChange={e => {
                                handleChangeThumbnail(e);
                                field.onChange(e.target.files?.[0] ?? null);
                            }}
                            name={field.name}
                            onBlur={field.onBlur}
                            disabled={field.disabled}
                        />
                    )}
                />
            </div>
            {isError && (
                <p className="text-alert-main text-sm">
                    画像サイズが大きすぎます（最大
                    {MAX_IMAGE_SIZE / 1024 / 1024}MB）
                </p>
            )}
        </div>
    );
};

export default ThumbnailEditField;
