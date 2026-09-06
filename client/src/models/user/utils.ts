import { icons } from '@dicebear/collection';
import { createAvatar, Result } from '@dicebear/core';

export const iconAvatar = (id: string): Result => {
    return createAvatar(icons, {
        seed: id,
        backgroundColor: [
            'b6e3f4', // 水色
            'ffd5dc', // ピンク
            'd1f7c4', // 黄緑
            'f4d03f', // 黄色
            'ffcfab', // オレンジ
            'bdc3c7', // グレー
            'e8daef', // 薄紫
            'aed6f1', // 青
        ],
    });
};

/** DiceBear SVG のライセンス metadata を除き、装飾画像として読み上げない HTML を返す */
export const iconAvatarHtml = (id: string): string => {
    return iconAvatar(id)
        .toString()
        .replace(/<metadata[\s\S]*?<\/metadata>/, '')
        .replace('<svg', '<svg aria-hidden="true" focusable="false"');
};
