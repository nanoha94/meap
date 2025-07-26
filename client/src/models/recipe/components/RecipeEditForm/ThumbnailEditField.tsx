import { IPostRecipeRequest } from '@/types/api/recipe';
import { ImagePlus, Trash } from 'lucide-react';
import Image from 'next/image';
import React from 'react';
import { Control, Controller } from 'react-hook-form';

interface Props {
    control: Control<IPostRecipeRequest>;
}

const ThumbnailEditField = ({ control }: Props) => {
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const [thumbnail, setThumbnail] = React.useState<File | null>(null);

    const handleChangeThumbnail = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setThumbnail(file);
        }
    };

    const handleDeleteThumbnail = () => {
        setThumbnail(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };
    return (
        <div className="relative w-full h-auto aspect-video bg-gray-light rounded-lg overflow-hidden transition-opcity">
            {/* サムネイルが設定されている場合 */}
            {thumbnail ? (
                <>
                    <Image
                        src={URL.createObjectURL(thumbnail)}
                        alt="thumbnail"
                        width={100}
                        height={100}
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
                name="thumbnailImage"
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
    );
};

export default ThumbnailEditField;
