/**
 * APIレスポンスの共通データ構造
 *
 * すべてのAPIレスポンスは以下の構造を持ちます:
 * - success: リクエストの成功/失敗を示すboolean値
 * - message: レスポンスメッセージ
 * - data: 実際のデータ（型はエンドポイントによって異なる）
 * - total: 一覧取得系のエンドポイントでのみ存在（件数）
 */

/**
 * 基本的なAPIレスポンス構造
 * @template T - dataプロパティの型
 */
export interface IBaseApiResponse<T> {
    success: boolean;
    message: string;
    data: T;
}

/**
 * 一覧取得系APIレスポンス構造（totalを含む）
 * @template T - dataプロパティの型（通常は配列）
 */
export interface IBaseApiIndexResponse<T> extends IBaseApiResponse<T> {
    total: number;
}

/**
 * 削除系APIレスポンス構造（dataがnull）
 */
export type IBaseApiDeleteResponse = IBaseApiResponse<null>;
