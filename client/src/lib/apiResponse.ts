/**
 * API レスポンスボディの扱い（クライアント共通）
 *
 * バックエンドが HTTP 200 を返しつつ JSON で `success: false` を返す場合、
 * Axios は reject しないため `catch` / `handleApiError` に入らない。
 * `Promise.allSettled` 後に fulfilled 結果を走査するとき用のヘルパー。
 */

/**
 * `Promise.allSettled` の 1 要素について、業務エラー相当なら表示用メッセージを返す。
 *
 * - **rejected**: ネットワークエラーや 4xx/5xx（Axios が throw）など。ここでは扱わず `null`
 *   （呼び出し側で `status === 'rejected'` を別途 `handleApiError` する想定）。
 * - **fulfilled**: 値は通常 `AxiosResponse` で、`value.data` が `IBaseApiResponse` 形
 *   `{ success, message, ... }`。`success === false` のときだけ `message` または `fallback`。
 *
 * @param result - `Promise.allSettled` の要素
 * @param fallback - `message` が空のときに使う既定文言
 * @returns スナックバー等に出す文字列。業務エラーでなければ `null`
 */
export function getApiErrorMessageFromSettledResult<T>(
    result: PromiseSettledResult<T>,
    fallback: string,
): string | null {
    // エラーの場合は null を返す
    if (result.status !== 'fulfilled') {
        return null;
    }

    const payload = result.value as { data?: { success?: boolean; message?: string } };
    const body = payload?.data;
    if (body && body.success === false) {
        return body.message || fallback;
    }
    return null;
}
