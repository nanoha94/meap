'use client';
import { icons } from '@dicebear/collection';
import { createAvatar } from '@dicebear/core';
import { TextButton } from '../../_components';
import { ChevronRight, Copy } from 'lucide-react';
import { useAuth } from '@/hooks';

const Page = () => {
    const { user } = useAuth();
    const avatar = createAvatar(icons, {
        seed: user?.custom_id,
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
        // backgroundType: ['solid'], // 背景のタイプを指定（デフォルトは'solid'）
    });

    const svgString = avatar.toString();

    return (
        <>
            <div className="py-7 flex gap-x-5">
                {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                <div
                    className="w-24 h-24 rounded-full overflow-hidden"
                    dangerouslySetInnerHTML={{ __html: svgString }}
                />
                <div className="flex flex-col gap-y-3">
                    <div className="flex flex-col gap-y-1">
                        <div className="text-xl">{user?.name}</div>
                        <div className="text-base text-gray-main">
                            @{user?.custom_id}
                        </div>
                    </div>
                    {/* TODO: プロフィール編集ページへ遷移 */}
                    <TextButton onClick={() => {}}>
                        プロフィールを編集
                        <ChevronRight />
                    </TextButton>
                    {/* TODO: 招待コードをコピー */}
                    <TextButton onClick={() => {}}>
                        招待コードをコピー
                        <Copy />
                    </TextButton>
                </div>
            </div>
            <div className="flex flex-col gap-y-6">
                <div className="text-xl">共有メンバー</div>
                <div className="flex flex-col gap-y-2">
                    <p>共有メンバーはまだいません。</p>
                    <TextButton onClick={() => {}}>
                        メンバーを追加
                        <ChevronRight />
                    </TextButton>
                </div>
            </div>
        </>
    );
};

export default Page;
