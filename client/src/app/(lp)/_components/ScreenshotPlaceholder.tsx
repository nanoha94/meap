import { Smartphone } from 'lucide-react';
import Image from 'next/image';

type ScreenshotPlaceholderProps = {
    screenName: string;
    className?: string;
    compact?: boolean;
    src?: string;
    imageWidth?: number;
    imageHeight?: number;
};

const frameClassName = (compact: boolean) =>
    `overflow-hidden rounded-[2rem] border-2 border-gray-border bg-white shadow-lg ${compact ? 'w-full sm:w-[220px] lg:w-[220px] xl:w-[240px]' : 'w-[230px] sm:w-[250px] lg:w-[270px]'}`;

/** LP 用スマホ画面スクショ。src 未指定時はプレースホルダーを表示 */
const ScreenshotPlaceholder = ({
    screenName,
    className,
    compact = false,
    src,
    imageWidth,
    imageHeight,
}: ScreenshotPlaceholderProps) => {
    const label = `${screenName}画面のスマホスクリーンショット${src ? '' : '（準備中）'}`;

    return (
        <div className={className ?? ''}>
            <div className={frameClassName(compact)} aria-label={label}>
                {src && imageWidth && imageHeight ? (
                    <Image
                        src={src}
                        alt={label}
                        width={imageWidth}
                        height={imageHeight}
                        className="h-auto w-full"
                    />
                ) : (
                    <>
                        <div className="flex justify-center bg-gray-background py-2.5">
                            <div
                                className="h-1.5 w-14 rounded-full bg-gray-iconFill"
                                aria-hidden
                            />
                        </div>
                        <div className="flex aspect-[9/16] flex-col items-center justify-center border-t border-dashed border-gray-border bg-primary-background/60 px-4">
                            <Smartphone
                                className="mb-3 h-7 w-7 text-gray-main"
                                strokeWidth={1.5}
                                aria-hidden
                            />
                            <p className="text-center text-sm font-bold text-black">
                                {screenName}
                            </p>
                            <p className="mt-1 text-center text-xs text-gray-main">
                                スマホ画面
                            </p>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
};

export default ScreenshotPlaceholder;
