/**
 * 一時的なIDを生成するためのカウンター
 * 同じタイミングで呼ばれた場合でも一意なIDを生成するため
 */
let idCounter = 0;

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
    const id = `${prefix}${++idCounter}-${Date.now()}`;
    return {
        ...defaultItem,
        id,
    };
}
