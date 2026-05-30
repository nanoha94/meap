/** 圧縮前の元ファイル上限（30 MB） */
export const MAX_ORIGINAL_IMAGE_SIZE = 30 * 1024 * 1024;

/** 圧縮後のアップロード上限（サーバー max:10240 KB と一致） */
export const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

/** サーバー ImageService::MAX_LONG_SIDE_PX と一致 */
export const IMAGE_COMPRESSION_MAX_LONG_SIDE_PX = 2000;

/** JPEG/WebP エンコード品質 */
export const IMAGE_COMPRESSION_QUALITY = 0.8;
