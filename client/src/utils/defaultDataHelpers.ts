/**
 * 一時的なIDを生成してオブジェクトに追加する
 * @param defaultItem デフォルトのオブジェクト
 * @param prefix IDのプレフィックス
 * @returns 一時的なIDが設定されたオブジェクト
 */
export function createDefaultData<T extends { id?: string }>(
    defaultItem: T,
    prefix: string,
): T & { id: string } {
    const id = `${prefix}${Date.now()}`;
    return {
        ...defaultItem,
        id,
    };
}
