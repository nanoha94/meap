/**
 * APIレスポンスの共通データ構造
 *
 * - BaseApiResponse: success, message, data: null（store / update / destroy / bulkXxx 等）
 * - BaseApiResponseWithData: success, message, data に 1 件（show 等）
 * - BaseApiIndexResponse: success, message, data（配列）, total（index）
 */

/**
 * ベースのAPIレスポンス（data: null。store / update / destroy / bulkXxx 用）
 */
export interface IBaseApiResponse {
    success: boolean;
    message: string;
    data: null;
}

/**
 * data に値が入るAPIレスポンス（show 等用）
 * @template T - data の型
 */
export interface IBaseApiResponseWithData<T> {
    success: boolean;
    message: string;
    data: T;
}

/**
 * 一覧取得用APIレスポンス（data: 配列等, total あり）
 * @template T - data の型（通常は配列）
 */
export interface IBaseApiIndexResponse<T> extends IBaseApiResponseWithData<T> {
    total: number;
}
