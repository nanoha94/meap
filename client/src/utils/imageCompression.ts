import imageCompression from 'browser-image-compression';

import {
    IMAGE_COMPRESSION_MAX_LONG_SIDE_PX,
    IMAGE_COMPRESSION_QUALITY,
    MAX_IMAGE_SIZE,
    MAX_ORIGINAL_IMAGE_SIZE,
} from '@/constants';

export class ImageCompressionError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'ImageCompressionError';
    }
}

/** 圧縮前の元ファイルサイズが上限内か検証する（選択時の早期チェック用） */
export function validateOriginalImageSize(file: File): void {
    if (file.size > MAX_ORIGINAL_IMAGE_SIZE) {
        throw new ImageCompressionError(
            `画像サイズが大きすぎます（最大${MAX_ORIGINAL_IMAGE_SIZE / 1024 / 1024}MB）`,
        );
    }
}

/**
 * 画像を長辺 2000px・quality 0.8 程度に圧縮する。
 * サーバー側リサイズ前の転送量を抑え、アップロード上限内に収める。
 */
export async function compressImage(file: File): Promise<File> {
    validateOriginalImageSize(file);

    const compressed = await imageCompression(file, {
        maxSizeMB: MAX_IMAGE_SIZE / 1024 / 1024,
        maxWidthOrHeight: IMAGE_COMPRESSION_MAX_LONG_SIDE_PX,
        initialQuality: IMAGE_COMPRESSION_QUALITY,
        useWebWorker: true,
    });

    if (compressed.size > MAX_IMAGE_SIZE) {
        throw new ImageCompressionError(
            `画像の圧縮後もサイズが大きすぎます（最大${MAX_IMAGE_SIZE / 1024 / 1024}MB）`,
        );
    }

    return new File([compressed], file.name, {
        type: compressed.type,
        lastModified: file.lastModified,
    });
}
