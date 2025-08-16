<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * 画像をアップロードしてデータベースに保存（新規作成）
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return Image
     */
    public function uploadAndSaveImage(
        UploadedFile $file,
        string $directory
    ): Image {
        try {
            return DB::transaction(function () use ($file, $directory) {
                $imageData = $this->uploadImage($file, $directory, null);

                // 新しい画像レコードを作成
                return Image::create([
                    'src' => $imageData['src'],
                    'width' => $imageData['width'],
                    'height' => $imageData['height'],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('画像アップロード・保存エラー', [
                'operation' => 'upload_and_save_image',
                'file_name' => $file->getClientOriginalName(),
                'directory' => $directory,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.upload_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 画像を更新（ファイルとデータベース）
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param Image $image
     * @return Image
     */
    public function updateImage(
        UploadedFile $file,
        string $directory,
        Image $image
    ): Image {
        try {
            return DB::transaction(function () use ($file, $directory, $image) {
                $imageData = $this->uploadImage($file, $directory, $image->src);

                // 同じ画像の場合は既存のレコードを返す
                if ($imageData['is_same_image']) {
                    return $image;
                }

                // 新しい画像の場合、既存の画像ファイルを削除   
                $this->deleteImage($image->src);

                // レコードを更新して返す
                $image->update([
                    'src' => $imageData['src'],
                    'width' => $imageData['width'],
                    'height' => $imageData['height'],
                ]);

                return $image;
            });
        } catch (\Exception $e) {
            Log::error('画像更新エラー', [
                'operation' => 'update_image',
                'file_name' => $file->getClientOriginalName(),
                'directory' => $directory,
                'image_id' => $image->id,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.update_failed') . ': ' . $e->getMessage());
        }
    }

    public function deleteImages(array $imageIds): int
    {
        try {
            return DB::transaction(function () use ($imageIds) {
                $images = Image::whereIn('id', $imageIds)->get();
                foreach ($images as $image) {
                    $path = $this->getStoragePath($image->src);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
                // データベースレコード一括削除
                Image::whereIn('id', $imageIds)->delete();
                return $images->count();
            });
        } catch (\Exception $e) {
            Log::error('画像一括削除エラー', [
                'operation' => 'delete_images',
                'image_ids' => $imageIds,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.bulk_deletion_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 画像のバリデーションルールを取得
     *
     * @param int $maxSizeKb 最大ファイルサイズ（KB）
     * @return array
     */
    public function getValidationRules(int $maxSizeKb = 2048): array
    {
        return [
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            "max:{$maxSizeKb}"
        ];
    }

    /**
     * 画像をアップロードして情報を返す（重複チェック付き）
     *
     * @param UploadedFile $file
     * @param string $directory アップロード先ディレクトリ
     * @param string|null $existingImageSrc 既存の画像Src（更新時）
     * @return array
     */
    private function uploadImage(
        UploadedFile $file,
        string $directory,
        ?string $existingImageSrc = null
    ): array {
        try {
            // 既存画像との重複チェック
            // アップロードするファイルのサイズが0の場合は、更新しないので、既存の画像を返す
            if ($file->getSize() === 0 || ($existingImageSrc && $this->isSameImage($file, $existingImageSrc))) {
                // 同じ画像の場合、既存の情報を返す
                $imageInfo = $this->getExistingImageInfo($existingImageSrc);
                return [
                    'src' => $existingImageSrc,
                    'width' => $imageInfo['width'],
                    'height' => $imageInfo['height'],
                    'is_same_image' => true // 同じ画像であることを示すフラグ
                ];
            }

            // 既存画像を削除（新しい画像の場合のみ）
            if ($existingImageSrc) {
                $this->deleteImage($existingImageSrc);
            }

            // 画像サイズを取得
            $imageInfo = getimagesize($file->getRealPath());
            if (!$imageInfo) {
                throw new \Exception('画像情報を取得できませんでした。');
            }

            // ファイルを保存
            $path = $file->store($directory, 'public');
            $src = '/storage/' . $path;

            return [
                'src' => $src,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'is_same_image' => false
            ];
        } catch (\Exception $e) {
            Log::error('画像アップロード処理エラー', [
                'operation' => 'upload_image',
                'file_name' => $file->getClientOriginalName(),
                'directory' => $directory,
                'existing_image_src' => $existingImageSrc,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * 画像ファイルを削除
     *
     * @param string|null $imageSrc
     * @return void
     */
    private function deleteImage(?string $imageSrc): void
    {
        if (empty($imageSrc)) {
            return;
        }

        try {
            $path = $this->getStoragePath($imageSrc);
            Storage::disk('public')->delete($path);
        } catch (\Exception $e) {
            Log::error('画像ファイル削除エラー', [
                'operation' => 'delete_image_file',
                'image_src' => $imageSrc,
                'storage_path' => $this->getStoragePath($imageSrc),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.deletion_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 新しい画像と既存の画像が同じかどうかをチェック
     *
     * @param UploadedFile $newFile
     * @param string $existingImageSrc
     * @return bool
     */
    private function isSameImage(UploadedFile $newFile, string $existingImageSrc): bool
    {
        try {
            // 既存ファイルのパスを取得
            $existingPath = $this->getStoragePath($existingImageSrc);
            $existingFullPath = Storage::disk('public')->path($existingPath);

            // 既存ファイルが存在するかチェック
            if (!Storage::disk('public')->exists($existingPath)) {
                return false;
            }

            // ファイルサイズの比較
            $newFileSize = $newFile->getSize();
            $existingFileSize = Storage::disk('public')->size($existingPath);

            if ($newFileSize !== $existingFileSize) {
                return false;
            }

            // ファイルハッシュで比較
            $newFileHash = hash_file('sha256', $newFile->getRealPath());
            $existingFileHash = hash_file('sha256', $existingFullPath);

            return $newFileHash === $existingFileHash;
        } catch (\Exception $e) {
            Log::error('画像比較処理エラー', [
                'operation' => 'is_same_image',
                'new_file_name' => $newFile->getClientOriginalName(),
                'existing_image_src' => $existingImageSrc,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.comparison_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 既存画像の情報を取得
     *
     * @param string $imageSrc
     * @return array
     */
    private function getExistingImageInfo(string $imageSrc): array
    {
        try {
            $existingPath = $this->getStoragePath($imageSrc);
            $existingFullPath = Storage::disk('public')->path($existingPath);

            if (!Storage::disk('public')->exists($existingPath)) {
                throw new \Exception('既存画像が見つかりません。');
            }

            $imageInfo = getimagesize($existingFullPath);
            if (!$imageInfo) {
                throw new \Exception('既存画像の情報を取得できませんでした。');
            }

            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        } catch (\Exception $e) {
            Log::error('既存画像情報取得エラー', [
                'operation' => 'get_existing_image_info',
                'image_src' => $imageSrc,
                'storage_path' => $this->getStoragePath($imageSrc),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception(__('api.image.info_get_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 画像Srcからストレージパスを取得
     *
     * @param string $imageSrc
     * @return string
     */
    private function getStoragePath(string $imageSrc): string
    {
        return str_replace('/storage/', '', $imageSrc);
    }
}
