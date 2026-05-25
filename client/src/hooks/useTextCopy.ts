import React from 'react';

/**
 * テキストをコピーするフック
 * @returns isTextCopied: テキストがコピーされたかどうか
 * @returns copyToClipboard: テキストをコピーする関数
 */
export const useTextCopy = () => {
    const [isCopied, setIsCopied] = React.useState(false);

    /**
     * テキストをコピーする
     * @param link コピーするテキスト
     */
    const copyToClipboard = React.useCallback(async (link: string) => {
        try {
            await navigator.clipboard.writeText(link);
            setIsCopied(true);
        } catch (err) {
            console.error(err);
        }
    }, []);

    // テキストをコピーしたら10秒後にリセットする
    React.useEffect(() => {
        if (isCopied) {
            setTimeout(() => {
                setIsCopied(false);
            }, 10000);
        }
    }, [isCopied]);

    return {
        isTextCopied: isCopied, // テキストがコピーされたかどうか
        copyToClipboard, // テキストをコピーする関数
    };
};
