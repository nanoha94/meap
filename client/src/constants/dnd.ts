/**
 * ドラッグ&ドロップ関連の定数
 */

/**
 * ドラッグを開始するために必要なマウスの移動距離（ピクセル）
 * 誤クリックを防ぐために使用
 */
export const DRAG_ACTIVATION_DISTANCE = 5;

/**
 * タッチ操作でドラッグを開始するまでの遅延時間（ミリ秒）
 * 誤タップを防ぐために使用
 */
export const TOUCH_ACTIVATION_DELAY = 250;

/**
 * タッチ操作でドラッグを開始するまでの許容移動距離（ピクセル）
 * スクロールとドラッグを区別するために使用
 */
export const TOUCH_ACTIVATION_TOLERANCE = 5;

export enum DND_SORTABLE_LIST_TYPE {
    GRID = 'grid',
    LIST = 'list',
}
